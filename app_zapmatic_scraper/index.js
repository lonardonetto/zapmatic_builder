const { chromium } = require('playwright');
const mysql = require('mysql2/promise');
const fs = require('fs');
const path = require('path');

// Lê o .env do sistema automaticamente — funciona em qualquer domínio
function parseEnv() {
    const envFile = path.resolve(__dirname, '..', '.env');
    if (!fs.existsSync(envFile)) {
        console.error('❌ .env não encontrado em', envFile);
        process.exit(1);
    }
    const env = {};
    const content = fs.readFileSync(envFile, 'utf8');
    for (const line of content.split('\n')) {
        const m = line.match(/^\s*([a-z_.]+)\s*=\s*(.+)/i);
        if (m) env[m[1]] = m[2].trim();
    }
    return env;
}

const env = parseEnv();

const dbConfig = {
    host: env['database.default.hostname'] || 'localhost',
    user: env['database.default.username'] || '',
    password: env['database.default.password'] || '',
    database: env['database.default.database'] || '',
    waitForConnections: true,
    connectionLimit: 10,
    queueLimit: 0
};

let browserInstance = null;

async function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms));
}

async function getBrowser(job) {
    if (!browserInstance) {
        const launchOptions = {
            headless: true,
            args: ['--no-sandbox', '--disable-setuid-sandbox']
        };
        if (job.proxy) {
            const proxies = job.proxy.split('\n').map(p => p.trim()).filter(p => p);
            if (proxies.length > 0) {
                const selectedProxy = proxies[Math.floor(Math.random() * proxies.length)];
                launchOptions.proxy = { server: selectedProxy };
                console.log(`[Job ${job.id}] Using Proxy: ${selectedProxy}`);
            }
        }
        browserInstance = await chromium.launch(launchOptions);
    }
    return browserInstance;
}

const LISTING_SELECTOR = 'div[role="feed"] a[href*="/maps/place/"]';

async function processJob(job, db) {
    console.log(`Starting job ${job.id}: ${job.keyword} in ${job.location}`);
    
    await db.query('UPDATE sp_gmscraper_jobs SET status = 1 WHERE id = ?', [job.id]);
    
    let context = null;
    try {
        const browser = await getBrowser(job);
        context = await browser.newContext({
            viewport: { width: 1280, height: 800 },
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        });
        const page = await context.newPage();
        
        const query = encodeURIComponent(`${job.keyword} ${job.location}`);
        const url = `https://www.google.com/maps/search/${query}`;
        await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 60000 });
        
        try {
            await page.waitForSelector(LISTING_SELECTOR, { timeout: 15000 });
        } catch(e) {
            console.log(`No results found for ${job.keyword}`);
            await db.query('UPDATE sp_gmscraper_jobs SET status = 2, error_msg = ? WHERE id = ?', ['Nenhum resultado encontrado.', job.id]);
            return;
        }

        const [existingLeads] = await db.query('SELECT phone FROM sp_gmscraper_leads WHERE job_id = ?', [job.id]);
        let seen = new Set(existingLeads.map(row => row.phone));
        let extractedCount = existingLeads.length;
        
        let currentCount = 0;
        let lastCount = 0;
        let noNewStreak = 0;

        while (currentCount < job.limit_leads) {
            const count = await page.$$eval(LISTING_SELECTOR, els => els.length);
            if (count >= job.limit_leads || noNewStreak >= 10) break;
            
            if (count === lastCount) noNewStreak++;
            else { noNewStreak = 0; lastCount = count; }
            
            await page.evaluate(() => {
                const feed = document.querySelector('div[role="feed"]');
                if (feed) feed.scrollBy(0, 1000);
            });
            await sleep(2000);
            currentCount = count;
        }

        const links = await page.$$eval(LISTING_SELECTOR, els => els.map(e => e.href));
        console.log(`Found ${links.length} potential leads. Processing up to ${job.limit_leads}...`);
        
        for (const link of links) {
            if (extractedCount >= job.limit_leads) break;
            
            const [check] = await db.query('SELECT status FROM sp_gmscraper_jobs WHERE id = ?', [job.id]);
            if (!check.length || check[0].status !== 1) {
                console.log(`Job ${job.id} stopped or paused.`);
                break;
            }

            try {
                console.log(`[Job ${job.id}] Navigating to: ${link.substring(0, 80)}...`);
                await page.goto(link, { waitUntil: 'domcontentloaded', timeout: 45000 });
                
                try {
                    await page.waitForSelector('h1', { timeout: 10000 });
                } catch(e) {}
                
                await sleep(2000);

                const data = await page.evaluate(() => {
                    const nameEl = document.querySelector('h1');
                    const name = nameEl ? nameEl.innerText.trim() : '';

                    const btns = Array.from(document.querySelectorAll('button, [data-item-id]'));
                    let phone = '';
                    let website = '';
                    let address = '';

                    for (const el of btns) {
                        const aria = el.getAttribute('aria-label') || '';
                        const itemId = el.getAttribute('data-item-id') || '';
                        const text = el.innerText || '';
                        
                        if (itemId.includes('phone') || aria.toLowerCase().includes('telefone') || aria.toLowerCase().includes('phone') || aria.toLowerCase().includes('ligue') || aria.toLowerCase().includes('call')) {
                            if (!phone) phone = text.replace(/[^0-9+]/g, '');
                        }
                        if (itemId.includes('authority') || aria.toLowerCase().includes('website') || aria.toLowerCase().includes('site')) {
                            if (!website && text.includes('.')) website = text.trim();
                        }
                        if (itemId.includes('address') || aria.toLowerCase().includes('endereço') || aria.toLowerCase().includes('address')) {
                            if (!address) address = text.trim();
                        }
                    }

                    if (!phone) {
                        const img = document.querySelector('img[src*="phone"]');
                        if (img && img.closest('button')) {
                            phone = img.closest('button').innerText.replace(/[^0-9+]/g, '');
                        }
                    }

                    let rating = '';
                    let reviews = '';
                    const ratingEl = document.querySelector('div.fontBodyMedium span[aria-label*="star"]');
                    if (ratingEl) {
                        const ariaStr = ratingEl.getAttribute('aria-label');
                        const m = ariaStr.match(/([\d\.]+)\s+stars?.*?\s+([\d,]+)\s+Reviews?/i) || ariaStr.match(/([\d\.]+)\s+estrelas?.*?\s+([\d,]+)\s+avalia/i);
                        if (m) {
                            rating = m[1];
                            reviews = m[2];
                        }
                    }

                    return { name, phone, website, address, rating, reviews };
                });

                if (data.name && data.phone && data.phone.length >= 8) {
                    console.log(`[Job ${job.id}] SUCCESS: Found ${data.name} - ${data.phone}`);
                    if (!seen.has(data.phone)) {
                        seen.add(data.phone);
                        
                        await db.query(
                            'INSERT INTO sp_gmscraper_leads (job_id, team_id, name, phone, rating, reviews, address, website, created) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                            [job.id, job.team_id, data.name, data.phone, data.rating, data.reviews, data.address, data.website, Math.floor(Date.now() / 1000)]
                        );

                        const [pbRows] = await db.query('SELECT id FROM sp_whatsapp_contacts WHERE ids = ?', [job.target_phonebook]);
                        const phonebookIdInt = pbRows.length > 0 ? pbRows[0].id : null;
                        
                        let cleanPhone = data.phone.replace(/\D/g, '');
                        const ddi = job.ddi || '55';
                        if (cleanPhone.length <= 11) {
                            cleanPhone = ddi + cleanPhone;
                        }

                        if (phonebookIdInt) {
                            const pids = Math.random().toString(36).substring(2, 15);
                            await db.query(
                                'INSERT INTO sp_whatsapp_phone_numbers (ids, team_id, pid, phone, params, is_valid) VALUES (?, ?, ?, ?, ?, 1)',
                                [pids, job.team_id, phonebookIdInt, cleanPhone, JSON.stringify({name: data.name})]
                            );
                        }

                        extractedCount++;
                        console.log(`[Job ${job.id}] Extracted: ${data.name} - ${cleanPhone} (${extractedCount}/${job.limit_leads})`);
                        
                        await db.query('UPDATE sp_gmscraper_jobs SET current_count = ? WHERE id = ?', [extractedCount, job.id]);
                        
                        if (extractedCount < job.limit_leads) {
                            console.log(`[Job ${job.id}] Waiting ${job.delay_seconds} seconds before next...`);
                            await sleep(job.delay_seconds * 1000);
                        }
                    } else {
                        console.log(`[Job ${job.id}] SKIPPED: Duplicate phone ${data.phone}`);
                    }
                } else {
                    console.log(`[Job ${job.id}] SKIPPED: No phone found or invalid (Name: ${data.name}, Phone: ${data.phone})`);
                }
            } catch (err) {
                console.error(`Error processing link: ${err.message}`);
            }
        }
        
        await db.query('UPDATE sp_gmscraper_jobs SET status = 2 WHERE id = ?', [job.id]);
        console.log(`Job ${job.id} completed!`);

    } catch (err) {
        console.error(`Job ${job.id} failed: ${err.message}`);
        await db.query('UPDATE sp_gmscraper_jobs SET status = 3, error_msg = ? WHERE id = ?', [err.message, job.id]);
    } finally {
        if (context) await context.close();
        if (browserInstance) {
            await browserInstance.close();
            browserInstance = null;
        }
    }
}

async function startDaemon() {
    console.log("Zapmatic Scraper Daemon Started...");
    const db = await mysql.createPool(dbConfig);
    
    while (true) {
        try {
            const [rows] = await db.query('SELECT * FROM sp_gmscraper_jobs WHERE status = 0 LIMIT 1');
            if (rows.length > 0) {
                await processJob(rows[0], db);
            }
        } catch(e) {
            console.error("Daemon error:", e);
        }
        await sleep(10000);
    }
}

startDaemon();
