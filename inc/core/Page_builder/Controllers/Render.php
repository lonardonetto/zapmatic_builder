<?php
namespace Core\Page_builder\Controllers;

class Render extends \CodeIgniter\Controller
{
    protected $tb_pages = 'sp_landing_pages';
    protected $tb_sections = 'sp_landing_sections';

    /**
     * Renderiza uma página específica pelo slug
     */
    public function page($slug = '')
    {
        if (empty($slug)) {
            return $this->renderHome();
        }
        $page = db_get("*", $this->tb_pages, ["slug" => $slug, "is_published" => 1]);
        if (empty($page)) {
            return redirect()->to(base_url());
        }
        return $this->renderPage($page);
    }

    /**
     * Renderiza a home via page builder
     */
    public function renderHome()
    {
        $page = db_get("*", $this->tb_pages, ["is_home" => 1, "is_published" => 1]);
        if (!empty($page)) {
            return $this->renderPage($page);
        }
        return (new \Core\Home\Controllers\Home())->index();
    }

    /**
     * Renderiza uma página
     */
    private function renderPage($page)
    {
        $sections = db_fetch("*", $this->tb_sections, ["page_id" => $page->id, "is_active" => 1], "sort_order", "ASC");
        $plans = db_fetch("*", TB_PLANS, ["status" => 1], "position", "ASC");
        $faqs = db_fetch("*", TB_FAQS, ["status" => 1], "id", "ASC", 0, 50);

        $template = $page->theme ?: get_option("frontend_template", "Stackdark");

        $content_html = '';
        foreach ($sections as $section) {
            $d = json_decode($section->data, true) ?: [];
            $s = json_decode($section->settings, true) ?: [];
            $content_html .= $this->renderBlock($section->block_type, $d, $s, $plans, $faqs);
        }

        $data = ["title" => $page->title, "content" => $content_html];
        return view("Frontend\\" . $template . "\Views\\index", $data);
    }

    private function renderBlock($type, $d, $s, $plans, $faqs)
    {
        $d = is_array($d) ? $d : [];
        $s = is_array($s) ? $s : [];
        switch ($type) {
            case 'hero': return $this->renderHero($d, $s);
            case 'features': return $this->renderFeatures($d, $s);
            case 'pricing': return $this->renderPricing($d, $s, $plans);
            case 'faq': return $this->renderFaq($d, $s, $faqs);
            case 'cta': return $this->renderCta($d, $s);
            case 'testimonials': return $this->renderTestimonials($d, $s);
            case 'footer': return $this->renderFooter($d, $s);
            case 'custom_html': return $this->renderCustomHtml($d, $s);
            default: return '';
        }
    }

    private function renderHero($d, $s)
    {
        $bg = ($d['background_type'] ?? 'color') == 'color'
            ? "background-color: " . ($d['background_color'] ?? '#0F0E17') . ";"
            : (($d['background_type'] ?? '') == 'image' ? "background-image: url(" . ($d['background_image'] ?? '') . "); background-size: cover;" : '');

        $pad_top = $s['padding_top'] ?? '100px';
        $pad_bot = $s['padding_bottom'] ?? '100px';

        $title = $d['hero_title'] ?? '';
        $subtitle = $d['hero_subtitle'] ?? '';
        $btn_text = $d['button_text'] ?? 'Começar';
        $btn_url = $d['button_url'] ?? '/signup';
        $btn_color = $d['button_color'] ?? '#6C5CE7';
        $text_color = $d['text_color'] ?? '#FFFFFF';
        $image = $d['image_right'] ?? '';
        $anim = $d['animation'] ?? 'fade-up';

        $img_col = $image ? '<div class="col-md-6" data-aos="fade-left"><img src="' . $image . '" class="w-100" alt=""></div>' : '';

        return '
        <div class="section banner m-b-100" id="home" style="' . $bg . ' padding-top:' . $pad_top . '; padding-bottom:' . $pad_bot . ';">
            <div class="container">
                <div class="row d-flex justify-content-between">
                    <div class="' . ($image ? 'col-md-6' : 'col-12') . ' d-flex align-items-center">
                        <div class="mb-4">
                            <div class="title mb-4" data-aos="' . $anim . '" style="color:' . $text_color . '">' . $title . '</div>
                            <div class="desc text-gray-500 mb-4" data-aos="' . $anim . '">' . $subtitle . '</div>
                            <div data-aos="' . $anim . '">
                                <a class="btn btn-round btn-primary me-3 text-uppercase" href="' . $btn_url . '" style="background:' . $btn_color . ';border-color:' . $btn_color . '">' . $btn_text . '</a>
                            </div>
                        </div>
                    </div>
                    ' . $img_col . '
                </div>
            </div>
        </div>';
    }

    private function renderFeatures($d, $s)
    {
        $title = $d['section_title'] ?? '';
        $subtitle = $d['section_subtitle'] ?? '';
        $cards = $d['cards'] ?? [];
        $cols = ($d['layout'] ?? '3') == '4' ? 'col-md-3' : 'col-md-4';
        $pad_top = $s['padding_top'] ?? '60px';
        $pad_bot = $s['padding_bottom'] ?? '60px';

        $html = '<div class="section features m-b-100" id="features" style="padding-top:' . $pad_top . '; padding-bottom:' . $pad_bot . ';">
            <div class="container">
                <div class="title text-center mb-4">' . $title . '</div>
                <p class="text-center text-gray-500 mb-5">' . $subtitle . '</p>
                <div class="row">';

        if (is_array($cards)) {
            foreach ($cards as $card) {
                $html .= '<div class="' . $cols . ' mb-4">
                    <div class="text-center p-4">
                        <div class="mb-3" style="font-size:48px">' . ($card['icon'] ?? '') . '</div>
                        <h5 class="mb-2">' . ($card['title'] ?? '') . '</h5>
                        <p class="text-gray-500">' . ($card['description'] ?? '') . '</p>
                    </div>
                </div>';
            }
        }

        $html .= '</div></div></div>';
        return $html;
    }

    private function renderPricing($d, $s, $plans)
    {
        $title = $d['section_title'] ?? '';
        $subtitle = $d['section_subtitle'] ?? '';
        $pad_top = $s['padding_top'] ?? '60px';
        $pad_bot = $s['padding_bottom'] ?? '60px';

        $html = '<div class="section pricing m-b-100" id="pricing" style="padding-top:' . $pad_top . '; padding-bottom:' . $pad_bot . ';">
            <div class="container">
                <div class="title text-center mb-4">' . $title . '</div>
                <p class="text-center text-gray-500 mb-5">' . $subtitle . '</p>
                <div class="row">';

        if (is_array($plans)) {
            foreach ($plans as $plan) {
                $desc = $plan->description ?? '';
                $price = number_format($plan->price_monthly ?? 0, 2, ',', '.');
                $html .= '<div class="col-md-4 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body text-center">
                            <h5 class="mb-2">' . $plan->name . '</h5>
                            <div class="mb-3">
                                <span style="font-size:36px;font-weight:bold">R$ ' . $price . '</span>
                                <small>/mês</small>
                            </div>
                            <p class="text-gray-500">' . $desc . '</p>
                            <a href="' . base_url('signup') . '" class="btn btn-round btn-primary w-100">Começar</a>
                        </div>
                    </div>
                </div>';
            }
        }

        $html .= '</div></div></div>';
        return $html;
    }

    private function renderFaq($d, $s, $faqs)
    {
        $title = $d['section_title'] ?? '';
        $pad_top = $s['padding_top'] ?? '60px';
        $pad_bot = $s['padding_bottom'] ?? '60px';

        $html = '<div class="section faq m-b-100" style="padding-top:' . $pad_top . '; padding-bottom:' . $pad_bot . ';">
            <div class="container">
                <div class="title text-center mb-5">' . $title . '</div>
                <div class="row justify-content-center"><div class="col-md-8">';

        if (($d['faq_origin'] ?? 'auto') == 'manual' && !empty($d['faqs'])) {
            $items = is_array($d['faqs']) ? $d['faqs'] : json_decode($d['faqs'], true);
            if (is_array($items)) {
                foreach ($items as $item) {
                    $html .= '<div class="mb-3 p-3 border rounded">
                        <h6 class="mb-2">' . ($item['q'] ?? '') . '</h6>
                        <p class="text-gray-500 mb-0">' . ($item['a'] ?? '') . '</p>
                    </div>';
                }
            }
        } elseif (is_array($faqs)) {
            foreach ($faqs as $faq) {
                $html .= '<div class="mb-3 p-3 border rounded">
                    <h6 class="mb-2">' . ($faq->title ?? '') . '</h6>
                    <p class="text-gray-500 mb-0">' . ($faq->content ?? '') . '</p>
                </div>';
            }
        }

        $html .= '</div></div></div></div>';
        return $html;
    }

    private function renderCta($d, $s)
    {
        $text = $d['cta_text'] ?? '';
        $sub = $d['cta_subtext'] ?? '';
        $btn = $d['cta_button_text'] ?? 'Começar';
        $url = $d['cta_button_url'] ?? '/signup';
        $bg = $d['cta_bg_color'] ?? '#6C5CE7';

        return '<div class="section cta text-center" style="background:' . $bg . '; padding:60px 0">
            <div class="container">
                <h2 class="text-white mb-3">' . $text . '</h2>
                <p class="text-white-50 mb-4">' . $sub . '</p>
                <a href="' . $url . '" class="btn btn-round btn-light btn-lg">' . $btn . '</a>
            </div>
        </div>';
    }

    private function renderTestimonials($d, $s)
    {
        $title = $d['section_title'] ?? '';
        $items = $d['testimonials'] ?? [];
        $pad_top = $s['padding_top'] ?? '60px';
        $pad_bot = $s['padding_bottom'] ?? '60px';

        $html = '<div class="section testimonials m-b-100" style="padding-top:' . $pad_top . '; padding-bottom:' . $pad_bot . ';">
            <div class="container">
                <div class="title text-center mb-5">' . $title . '</div>
                <div class="row">';

        if (is_array($items)) {
            foreach ($items as $item) {
                $photo = $item['photo'] ?? '';
                $html .= '<div class="col-md-4 mb-4">
                    <div class="card shadow-sm text-center p-4">
                        ' . ($photo ? '<img src="' . $photo . '" class="rounded-circle mb-3" width="80" height="80" style="object-fit:cover">' : '') . '
                        <p class="mb-3">"' . ($item['text'] ?? '') . '"</p>
                        <strong>' . ($item['name'] ?? '') . '</strong>
                        <div class="text-gray-500">' . ($item['role'] ?? '') . '</div>
                    </div>
                </div>';
            }
        }

        $html .= '</div></div></div>';
        return $html;
    }

    private function renderFooter($d, $s)
    {
        $desc = $d['footer_description'] ?? '';
        $links = $d['footer_links'] ?? [];
        $socials = $d['social_links'] ?? [];
        $copy = $d['copyright'] ?? '';

        $html = '<footer class="bg-dark text-white py-5">
            <div class="container">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <p>' . $desc . '</p>
                        <div class="mt-3">';
        if (is_array($socials)) {
            foreach ($socials as $social) {
                $html .= '<a href="' . ($social['url'] ?? '#') . '" class="text-white me-2" target="_blank"><i class="' . ($social['icon'] ?? '') . ' fa-lg"></i></a>';
            }
        }
        $html .= '</div></div>';

        if (is_array($links)) {
            $html .= '<div class="col-md-4 mb-3">
                <h6 class="mb-3">Links Úteis</h6>
                <ul class="list-unstyled">';
            foreach ($links as $link) {
                $html .= '<li class="mb-2"><a href="' . ($link['url'] ?? '#') . '" class="text-white-50">' . ($link['title'] ?? '') . '</a></li>';
            }
            $html .= '</ul></div>';
        }

        $html .= '</div><hr class="border-secondary"><div class="text-center text-white-50"><small>' . $copy . '</small></div></div></footer>';
        return $html;
    }

    private function renderCustomHtml($d, $s)
    {
        $css = ($d['custom_css'] ?? '') ? '<style>' . ($d['custom_css'] ?? '') . '</style>' : '';
        return $css . ($d['custom_html'] ?? '');
    }
}
