<?php
namespace Core\Page_builder\Controllers;

class Render extends \CodeIgniter\Controller
{
    protected $tb_pages = 'sp_landing_pages';
    protected $tb_sections = 'sp_landing_sections';

    /**
     * Renderiza uma página específica pelo slug
     * Rota: /pagina/{slug}
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
     * Renderiza a página principal (home)
     */
    public function renderHome()
    {
        // Buscar página marcada como home e publicada
        $page = db_get("*", $this->tb_pages, ["is_home" => 1, "is_published" => 1]);

        if (!empty($page)) {
            return $this->renderPage($page);
        }

        // Fallback: usar Home controller padrão
        return (new \Core\Home\Controllers\Home())->index();
    }

    /**
     * Renderiza uma página com suas seções
     */
    private function renderPage($page)
    {
        $sections = db_fetch("*", $this->tb_sections, ["page_id" => $page->id, "is_active" => 1], "sort_order", "ASC");

        // Buscar planos para o bloco pricing
        $plans = db_fetch("*", TB_PLANS, ["status" => 1], "position", "ASC");

        // Buscar FAQs para o bloco FAQ
        $faqs = db_fetch("*", TB_FAQS, ["status" => 1], "id", "ASC", 0, 50);

        // Tema base
        $template = $page->theme ?: get_option("frontend_template", "Stackdark");

        // Renderizar cada seção
        $content_html = '';
        foreach ($sections as $section) {
            $data = json_decode($section->data, true) ?: [];
            $settings = json_decode($section->settings, true) ?: [];

            $content_html .= $this->renderBlock($section->block_type, $data, $settings, $plans, $faqs);
        }

        $data = [
            "title" => $page->title,
            "content" => $content_html,
        ];

        return view("Frontend\\" . $template . "\Views\\index", $data);
    }

    /**
     * Renderiza um bloco individual
     */
    private function renderBlock($blockType, $data, $settings, $plans, $faqs)
    {
        switch ($blockType) {
            case 'hero':
                return $this->renderHero($data, $settings);
            case 'features':
                return $this->renderFeatures($data, $settings);
            case 'pricing':
                return $this->renderPricing($data, $settings, $plans);
            case 'faq':
                return $this->renderFaq($data, $settings, $faqs);
            case 'cta':
                return $this->renderCta($data, $settings);
            case 'testimonials':
                return $this->renderTestimonials($data, $settings);
            case 'footer':
                return $this->renderFooter($data, $settings);
            case 'custom_html':
                return $this->renderCustomHtml($data, $settings);
            default:
                return '';
        }
    }

    private function renderHero($d, $s)
    {
        $bg = $d['background_type'] == 'color' 
            ? "background-color: {$d['background_color']};" 
            : ($d['background_type'] == 'image' ? "background-image: url({$d['background_image']}); background-size: cover;" : "");

        return '
        <div class="section banner m-b-100" id="home" style="'.$bg.' padding-top:'.$s['padding_top'].'; padding-bottom:'.$s['padding_bottom'].'">
            <div class="container">
                <div class="row d-flex justify-content-between">
                    <div class="col-md-6 d-flex align-items-center">
                        <div class="mb-4">
                            <div class="title mb-4" data-aos="'.$d['animation'].'" style="color:'.$d['text_color'].'">'.$d['hero_title'].'</div>
                            <div class="desc text-gray-500 mb-4" data-aos="'.$d['animation'].'">'.$d['hero_subtitle'].'</div>
                            <div data-aos="'.$d['animation'].'">
                                <a class="btn btn-round btn-primary me-3 text-uppercase" href="'.$d['button_url'].'" style="background:'.$d['button_color'].';border-color:'.$d['button_color'].'">'.$d['button_text'].'</a>
                            </div>
                        </div>
                    </div>
                    '.($d['image_right'] ? '<div class="col-md-6" data-aos="fade-left"><img src="'.$d['image_right'].'" class="w-100" alt=""></div>' : '').'
                </div>
            </div>
        </div>';
    }

    private function renderFeatures($d, $s)
    {
        $cards = $d['cards'] ?? [];
        $cols = $d['layout'] == '4' ? 'col-md-3' : 'col-md-4';
        $html = '<div class="section features m-b-100" id="features" style="padding-top:'.$s['padding_top'].'; padding-bottom:'.$s['padding_bottom'].'">
            <div class="container">
                <div class="title text-center mb-4">'.$d['section_title'].'</div>
                <p class="text-center text-gray-500 mb-5">'.$d['section_subtitle'].'</p>
                <div class="row">';
        
        if (is_array($cards)) {
            foreach ($cards as $card) {
                $html .= '<div class="'.$cols.' mb-4">
                    <div class="text-center p-4">
                        <div class="mb-3" style="font-size:48px">'.$card['icon'].'</div>
                        <h5 class="mb-2">'.$card['title'].'</h5>
                        <p class="text-gray-500">'.$card['description'].'</p>
                    </div>
                </div>';
            }
        }
        
        $html .= '</div></div></div>';
        return $html;
    }

    private function renderPricing($d, $s, $plans)
    {
        $html = '<div class="section pricing m-b-100" id="pricing" style="padding-top:'.$s['padding_top'].'; padding-bottom:'.$s['padding_bottom'].'">
            <div class="container">
                <div class="title text-center mb-4">'.$d['section_title'].'</div>
                <p class="text-center text-gray-500 mb-5">'.$d['section_subtitle'].'</p>
                <div class="row">';

        foreach ($plans as $plan) {
            $html .= '<div class="col-md-4 mb-4">
                <div class="card shadow-sm h-100">
                    <div class="card-body text-center">
                        <h5 class="mb-2">'.$plan->name.'</h5>
                        <div class="mb-3">
                            <span style="font-size:36px;font-weight:bold">R$ '.number_format($plan->price_monthly, 2, ',', '.').'</span>
                            <small>/mês</small>
                        </div>
                        <p class="text-gray-500">'.$plan->description.'</p>
                        <a href="'.base_url('signup').'" class="btn btn-round btn-primary w-100">Começar</a>
                    </div>
                </div>
            </div>';
        }

        $html .= '</div></div></div>';
        return $html;
    }

    private function renderFaq($d, $s, $faqs)
    {
        $html = '<div class="section faq m-b-100" style="padding-top:'.$s['padding_top'].'; padding-bottom:'.$s['padding_bottom'].'">
            <div class="container">
                <div class="title text-center mb-5">'.$d['section_title'].'</div>
                <div class="row justify-content-center"><div class="col-md-8">';

        if ($d['faq_origin'] == 'manual' && !empty($d['faqs'])) {
            $items = is_array($d['faqs']) ? $d['faqs'] : json_decode($d['faqs'], true);
            foreach ($items as $item) {
                $html .= '<div class="mb-3 p-3 border rounded">
                    <h6 class="mb-2">'.$item['q'].'</h6>
                    <p class="text-gray-500 mb-0">'.$item['a'].'</p>
                </div>';
            }
        } else {
            foreach ($faqs as $faq) {
                $html .= '<div class="mb-3 p-3 border rounded">
                    <h6 class="mb-2">'.$faq->title.'</h6>
                    <p class="text-gray-500 mb-0">'.$faq->content.'</p>
                </div>';
            }
        }

        $html .= '</div></div></div></div>';
        return $html;
    }

    private function renderCta($d, $s)
    {
        return '<div class="section cta text-center" style="background:'.$d['cta_bg_color'].'; padding:60px 0">
            <div class="container">
                <h2 class="text-white mb-3">'.$d['cta_text'].'</h2>
                <p class="text-white-50 mb-4">'.$d['cta_subtext'].'</p>
                <a href="'.$d['cta_button_url'].'" class="btn btn-round btn-light btn-lg">'.$d['cta_button_text'].'</a>
            </div>
        </div>';
    }

    private function renderTestimonials($d, $s)
    {
        $items = $d['testimonials'] ?? [];
        $html = '<div class="section testimonials m-b-100" style="padding-top:'.$s['padding_top'].'; padding-bottom:'.$s['padding_bottom'].'">
            <div class="container">
                <div class="title text-center mb-5">'.$d['section_title'].'</div>
                <div class="row">';

        if (is_array($items)) {
            foreach ($items as $item) {
                $html .= '<div class="col-md-4 mb-4">
                    <div class="card shadow-sm text-center p-4">
                        '.($item['photo'] ? '<img src="'.$item['photo'].'" class="rounded-circle mb-3" width="80" height="80" style="object-fit:cover">' : '').'
                        <p class="mb-3">"'.$item['text'].'"</p>
                        <strong>'.$item['name'].'</strong>
                        <div class="text-gray-500">'.$item['role'].'</div>
                    </div>
                </div>';
            }
        }

        $html .= '</div></div></div>';
        return $html;
    }

    private function renderFooter($d, $s)
    {
        $links = $d['footer_links'] ?? [];
        $socials = $d['social_links'] ?? [];

        $html = '<footer class="bg-dark text-white py-5">
            <div class="container">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <p>'.$d['footer_description'].'</p>
                        <div class="mt-3">';
        if (is_array($socials)) {
            foreach ($socials as $social) {
                $html .= '<a href="'.$social['url'].'" class="text-white me-2" target="_blank"><i class="'.$social['icon'].' fa-lg"></i></a>';
            }
        }
        $html .= '</div></div>';

        if (is_array($links)) {
            $html .= '<div class="col-md-4 mb-3">
                <h6 class="mb-3">Links Úteis</h6>
                <ul class="list-unstyled">';
            foreach ($links as $link) {
                $html .= '<li class="mb-2"><a href="'.$link['url'].'" class="text-white-50">'.$link['title'].'</a></li>';
            }
            $html .= '</ul></div>';
        }

        $html .= '</div><hr class="border-secondary"><div class="text-center text-white-50"><small>'.$d['copyright'].'</small></div></div></footer>';
        return $html;
    }

    private function renderCustomHtml($d, $s)
    {
        $css = $d['custom_css'] ? '<style>'.$d['custom_css'].'</style>' : '';
        return $css . $d['custom_html'];
    }
}
