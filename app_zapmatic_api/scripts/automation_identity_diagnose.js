const Common = require("../waziper/common.js");

const printSection = (title, rows, formatter = (row) => JSON.stringify(row)) => {
	console.log(`\n## ${title}`);
	if (!rows || rows.length === 0) {
		console.log("none");
		return;
	}

	rows.forEach((row, index) => {
		console.log(`${index + 1}. ${formatter(row)}`);
	});
};

const parseContactData = (raw) => {
	if (!raw) return {};
	if (typeof raw === "object") return raw;
	try {
		return JSON.parse(raw);
	} catch (error) {
		return {};
	}
};

const main = async () => {
	try {
		const cloudSubscribers = await Common.db_query(
			`SELECT s.id, s.instance_id, s.chatid, s.contact_data
			 FROM sp_whatsapp_subscriber s
			 INNER JOIN sp_accounts a ON a.token = s.instance_id
			 WHERE a.login_type = 1
			   AND (
					s.chatid NOT REGEXP '^[0-9]+@s\\\\.whatsapp\\\\.net$'
					OR JSON_UNQUOTE(JSON_EXTRACT(s.contact_data, '$.number')) REGEXP '^[0-9]+$'
					   AND s.chatid <> CONCAT(JSON_UNQUOTE(JSON_EXTRACT(s.contact_data, '$.number')), '@s.whatsapp.net')
			   )
			 ORDER BY s.instance_id, s.id DESC
			 LIMIT 50`,
			false
		);

		const cloudResponses = await Common.db_query(
			`SELECT r.id, r.instance_id, r.whatsapp, r.last_response
			 FROM sp_whatsapp_ar_responses r
			 INNER JOIN sp_accounts a ON a.token = r.instance_id
			 WHERE a.login_type = 1
			   AND r.whatsapp NOT REGEXP '^[0-9]+$'
			 ORDER BY r.instance_id, r.id DESC
			 LIMIT 50`,
			false
		);

		const lidSubscribers = await Common.db_query(
			`SELECT s.id, s.instance_id, s.chatid, s.contact_data
			 FROM sp_whatsapp_subscriber s
			 INNER JOIN sp_accounts a ON a.token = s.instance_id
			 WHERE a.login_type <> 1
			   AND (
					s.chatid LIKE '%@lid'
					OR JSON_UNQUOTE(JSON_EXTRACT(s.contact_data, '$.transport_jid')) LIKE '%@lid'
					OR JSON_UNQUOTE(JSON_EXTRACT(s.contact_data, '$.identity_jid')) LIKE '%@lid'
			   )
			 ORDER BY s.instance_id, s.id DESC
			 LIMIT 50`,
			false
		);

		const duplicateCanonicalRows = await Common.db_query(
			`SELECT s.instance_id,
			        JSON_UNQUOTE(JSON_EXTRACT(s.contact_data, '$.number')) AS canonical_number,
			        COUNT(*) AS total_rows,
			        GROUP_CONCAT(s.id ORDER BY s.id DESC) AS subscriber_ids,
			        GROUP_CONCAT(s.chatid ORDER BY s.id DESC SEPARATOR ' | ') AS chatids
			 FROM sp_whatsapp_subscriber s
			 WHERE JSON_UNQUOTE(JSON_EXTRACT(s.contact_data, '$.number')) REGEXP '^[0-9]+$'
			 GROUP BY s.instance_id, canonical_number
			 HAVING COUNT(*) > 1
			 ORDER BY total_rows DESC, s.instance_id
			 LIMIT 50`,
			false
		);

		printSection(
			"Cloud subscribers with non-canonical identity",
			cloudSubscribers,
			(row) => {
				const contact = parseContactData(row.contact_data);
				return [
					`instance=${row.instance_id}`,
					`subscriber=${row.id}`,
					`chatid=${row.chatid}`,
					`number=${contact.number || ""}`,
					`bsuid=${contact.bsuid || ""}`,
					`identity_key=${contact.identity_key || ""}`
				].join(" | ");
			}
		);

		printSection(
			"Cloud autoresponder delay rows using legacy identity",
			cloudResponses,
			(row) => [
				`instance=${row.instance_id}`,
				`row=${row.id}`,
				`whatsapp=${row.whatsapp}`,
				`last_response=${row.last_response}`
			].join(" | ")
		);

		printSection(
			"Baileys subscribers still using @lid transport",
			lidSubscribers,
			(row) => {
				const contact = parseContactData(row.contact_data);
				return [
					`instance=${row.instance_id}`,
					`subscriber=${row.id}`,
					`chatid=${row.chatid}`,
					`number=${contact.number || ""}`,
					`transport_jid=${contact.transport_jid || ""}`,
					`identity_jid=${contact.identity_jid || ""}`
				].join(" | ");
			}
		);

		printSection(
			"Duplicate canonical subscriber identities",
			duplicateCanonicalRows,
			(row) => [
				`instance=${row.instance_id}`,
				`number=${row.canonical_number}`,
				`rows=${row.total_rows}`,
				`subscriber_ids=${row.subscriber_ids}`,
				`chatids=${row.chatids}`
			].join(" | ")
		);
	} catch (error) {
		console.error("automation identity diagnose failed:", error.message || error);
		process.exitCode = 1;
	} finally {
		Common.db_connect.end(() => process.exit(process.exitCode || 0));
	}
};

main();
