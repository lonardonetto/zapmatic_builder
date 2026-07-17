<?php
namespace Core\Page_builder\Controllers;

class Page_builder extends \CodeIgniter\Controller
{
    protected $tb_pages = 'sp_landing_pages';
    protected $tb_sections = 'sp_landing_sections';

    public function __construct()
    {
        $this->config = parse_config(include realpath(__DIR__ . "/../Config.php"));
    }

    /**
     * Lista de páginas
     */
    public function index()
    {
        $team_id = get_team("id");
        // Show pages for this team OR global pages (team_id IS NULL)
        $pages = db_fetch("*", $this->tb_pages, "", "id", "DESC");

        // Contar seções por página
        foreach ($pages as &$page) {
            if (isset($page->id)) {
                $count = db_get("COUNT(*) as cnt", $this->tb_sections, ["page_id" => $page->id], "", "", false);
                $page->section_count = $count->cnt ?? 0;
            } else {
                $page->section_count = 0;
            }
        }

        $data = [
            "title" => $this->config['name'],
            "desc" => $this->config['desc'],
            "pages" => $pages,
            "content" => view('Core\Page_builder\Views\list', ["pages" => $pages]),
        ];

        return view('Core\Page_builder\Views\index', $data);
    }

    /**
     * Salva uma nova página ou atualiza existente
     */
    public function save_page()
    {
        $team_id = get_team("id");
        $id = (int) post('id');
        $title = post('title');
        $slug = post('slug');
        $page_type = post('page_type') ?: 'custom';
        $is_home = (int) post('is_home');
        $is_published = (int) post('is_published');
        $theme = post('theme') ?: null;

        if (empty($title)) {
            ms(["status" => "error", "message" => "Título é obrigatório"]);
        }

        // Auto-gerar slug se vazio
        if (empty($slug)) {
            $map = ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','í'=>'i','ì'=>'i','î'=>'i','ï'=>'i','ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o','ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','ç'=>'c','ñ'=>'n'];
            $slug = strtr(mb_strtolower(trim($title)), $map);
            $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
            $slug = trim($slug, '-');
            if (empty($slug)) $slug = 'page-' . time();
        }

        $data = [
            "title" => $title,
            "slug" => $slug,
            "page_type" => $page_type,
            "is_home" => $is_home,
            "is_published" => $is_published,
            "theme" => $theme,
            "team_id" => $team_id,
            "changed" => time(),
        ];

        if ($id > 0) {
            // Atualizar
            db_update($this->tb_pages, $data, ["id" => $id]);
            $page_id = $id;
        } else {
            // Se for home, desmarcar outras
            if ($is_home) {
                db_update($this->tb_pages, ["is_home" => 0], ["team_id" => $team_id]);
            }

            $data["ids"] = ids();
            $data["created"] = time();
            $page_id = db_insert($this->tb_pages, $data);
        }

        ms(["status" => "success", "message" => "Página salva com sucesso!", "page_id" => $page_id, "redirect" => get_module_url("editor/" . $page_id)]);
    }

    /**
     * Editor de seções da página
     */
    public function editor($page_id = 0)
    {
        $page = db_get("*", $this->tb_pages, ["id" => $page_id]);

        if (empty($page)) {
            redirect_to(get_module_url());
        }

        $sections = db_fetch("*", $this->tb_sections, ["page_id" => $page_id], "sort_order", "ASC");

        // Preparar blocos disponíveis
        $blocks = $this->getAvailableBlocks();

        $data = [
            "title" => "Editando: " . $page->title,
            "desc" => "Arraste os blocos para montar a página",
            "page" => $page,
            "sections" => $sections,
            "blocks" => $blocks,
            "content" => view('Core\Page_builder\Views\editor', [
                "page" => $page,
                "sections" => $sections,
                "blocks" => $blocks
            ]),
        ];

        return view('Core\Page_builder\Views\index', $data);
    }

    /**
     * Salva uma seção (criar ou atualizar)
     */
    public function save_section()
    {
        $page_id = (int) post('page_id');
        $section_id = (int) post('section_id');
        $block_type = post('block_type');
        $title = post('section_title') ?: ucfirst($block_type);

        // Coletar dados do formulário como JSON
        $data = [];
        $settings = [];

        // Campos de conteúdo (data)
        $content_fields = [
            'hero_title', 'hero_subtitle', 'button_text', 'button_url', 'button_color',
            'background_type', 'background_color', 'background_image', 'text_color',
            'subtitle_color', 'animation', 'image_right', 'height',
            'section_title', 'section_subtitle', 'layout', 'cards',
            'pricing_origin', 'highlight_plan',
            'faq_origin', 'faqs', 'items',
            'cta_text', 'cta_subtext', 'cta_button_text', 'cta_button_url', 'cta_bg_color', 'cta_text_color',
            'footer_logo', 'footer_description', 'social_links', 'footer_links', 'copyright',
            'custom_html', 'custom_css',
            'testimonials', 'stats_items', 'blog_count', 'blog_style',
        ];

        foreach ($content_fields as $field) {
            if (isset($_POST[$field])) {
                $val = post($field);
                // Decodificar JSON se for array de cards/items
                if (is_string($val) && (strpos($val, '[') === 0 || strpos($val, '{') === 0)) {
                    $decoded = json_decode($val, true);
                    if ($decoded !== null) {
                        $val = $decoded;
                    }
                }
                $data[$field] = $val;
            }
        }

        // Campos de estilo (settings)
        $style_fields = ['padding_top', 'padding_bottom', 'margin_top', 'margin_bottom', 'full_width'];
        foreach ($style_fields as $field) {
            if (isset($_POST[$field])) {
                $settings[$field] = post($field);
            }
        }

        // Determinar sort_order
        $max_order = db_get("MAX(sort_order) as max_order", $this->tb_sections, ["page_id" => $page_id]);
        $sort_order = $max_order && $max_order->max_order !== null ? $max_order->max_order + 1 : 0;

        $db_data = [
            "page_id" => $page_id,
            "block_type" => $block_type,
            "title" => $title,
            "data" => json_encode($data, JSON_UNESCAPED_UNICODE),
            "settings" => json_encode($settings, JSON_UNESCAPED_UNICODE),
            "changed" => time(),
        ];

        if ($section_id > 0) {
            db_update($this->tb_sections, $db_data, ["id" => $section_id]);
            header('Content-Type: application/json');
            echo json_encode(["status" => "success", "message" => "Seção atualizada!"], JSON_UNESCAPED_UNICODE);
            exit;
        } else {
            $db_data["sort_order"] = $sort_order;
            $db_data["created"] = time();
            $section_id = db_insert($this->tb_sections, $db_data);
            header('Content-Type: application/json');
            echo json_encode(["status" => "success", "message" => "Seção adicionada!", "section_id" => $section_id], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Deleta uma seção
     */
    public function delete_section()
    {
        $id = (int) post('id');
        db_delete($this->tb_sections, ["id" => $id]);
        header('Content-Type: application/json');
        echo json_encode(["status" => "success", "message" => "Seção removida!"]);
        exit;
    }

    /**
     * Reordena as seções
     */
    public function reorder_sections()
    {
        $order = json_decode(post('order'), true);
        if (is_array($order)) {
            foreach ($order as $i => $section_id) {
                db_update($this->tb_sections, ["sort_order" => $i], ["id" => (int) $section_id]);
            }
        }
        header('Content-Type: application/json');
        echo json_encode(["status" => "success"]);
        exit;
    }

    /**
     * Deleta uma página
     */
    public function delete_page()
    {
        $id = (int) post('id');
        db_delete($this->tb_sections, ["page_id" => $id]);
        db_delete($this->tb_pages, ["id" => $id]);
        ms(["status" => "success", "message" => "Página deletada!"]);
    }

    /**
     * Alterna publicação da página
     */
    public function toggle_page()
    {
        $id = (int) post('id');
        $page = db_get("*", $this->tb_pages, ["id" => $id]);
        if ($page) {
            db_update($this->tb_pages, ["is_published" => $page->is_published ? 0 : 1], ["id" => $id]);
        }
        header('Content-Type: application/json');
        echo json_encode(["status" => "success"]);
        exit;
    }

    /**
     * Move o loadBlockForm para view
     */
    public function loadBlockForm($blockType, $data)
    {
        return $this->getBlockFormHtml($blockType, $data);
    }

    private function getBlockFormHtml($blockType, $data)
    {
        $d = is_array($data) ? $data : [];
        switch ($blockType) {
            case 'hero':
                return '<div class="row g-3">
                    <div class="col-md-6"><label class="form-label">Título</label><input type="text" class="form-control" name="hero_title" value="'.($d['hero_title']??'').'"></div>
                    <div class="col-md-6"><label class="form-label">Cor do Título</label><input type="color" class="form-control form-control-color" name="text_color" value="'.($d['text_color']??'#FFFFFF').'"></div>
                    <div class="col-12"><label class="form-label">Subtítulo</label><textarea class="form-control" name="hero_subtitle" rows="2">'.($d['hero_subtitle']??'').'</textarea></div>
                    <div class="col-md-4"><label class="form-label">Texto do Botão</label><input type="text" class="form-control" name="button_text" value="'.($d['button_text']??'').'"></div>
                    <div class="col-md-4"><label class="form-label">URL do Botão</label><input type="text" class="form-control" name="button_url" value="'.($d['button_url']??'').'"></div>
                    <div class="col-md-4"><label class="form-label">Cor do Botão</label><input type="color" class="form-control form-control-color" name="button_color" value="'.($d['button_color']??'#6C5CE7').'"></div>
                    <div class="col-md-4"><label class="form-label">Tipo Fundo</label><select class="form-select" name="background_type">
                        <option value="color" '.($d['background_type']??''=='color'?'selected':'').'>Cor Sólida</option>
                        <option value="image" '.($d['background_type']??''=='image'?'selected':'').'>Imagem</option>
                        <option value="gradient" '.($d['background_type']??''=='gradient'?'selected':'').'>Gradiente</option>
                    </select></div>
                    <div class="col-md-4"><label class="form-label">Cor Fundo</label><input type="color" class="form-control form-control-color" name="background_color" value="'.($d['background_color']??'#0F0E17').'"></div>
                    <div class="col-md-4"><label class="form-label">Imagem Fundo</label><input type="text" class="form-control" name="background_image" value="'.($d['background_image']??'').'"></div>
                    <div class="col-md-4"><label class="form-label">Imagem Direita</label><input type="text" class="form-control" name="image_right" value="'.($d['image_right']??'').'"></div>
                </div>';

            case 'features':
                return '<div class="row g-3">
                    <div class="col-12"><label class="form-label">Título</label><input type="text" class="form-control" name="section_title" value="'.($d['section_title']??'').'"></div>
                    <div class="col-12"><label class="form-label">Subtítulo</label><textarea class="form-control" name="section_subtitle" rows="2">'.($d['section_subtitle']??'').'</textarea></div>
                    <div class="col-md-6"><label class="form-label">Layout</label><select class="form-select" name="layout">
                        <option value="3" '.($d['layout']??''=='3'?'selected':'').'>3 Colunas</option>
                        <option value="4" '.($d['layout']??''=='4'?'selected':'').'>4 Colunas</option>
                    </select></div>
                    <div class="col-12"><label class="form-label">Cards (JSON)</label><textarea class="form-control" name="cards" rows="6">'.json_encode($d['cards']??[], JSON_UNESCAPED_UNICODE).'</textarea>
                    <small class="text-muted">Formato: [{"icon":"🚀","title":"título","description":"descrição"}]</small></div>
                </div>';

            case 'pricing':
                return '<div class="row g-3">
                    <div class="col-12"><label class="form-label">Título</label><input type="text" class="form-control" name="section_title" value="'.($d['section_title']??'').'"></div>
                    <div class="col-12"><label class="form-label">Subtítulo</label><textarea class="form-control" name="section_subtitle" rows="2">'.($d['section_subtitle']??'').'</textarea></div>
                    <div class="col-12"><label class="form-label">Origem</label><select class="form-select" name="pricing_origin">
                        <option value="auto" '.($d['pricing_origin']??''=='auto'?'selected':'').'>Automático (do banco)</option>
                        <option value="manual" '.($d['pricing_origin']??''=='manual'?'selected':'').'>Manual (JSON)</option>
                    </select></div>
                </div>';

            case 'faq':
                return '<div class="row g-3">
                    <div class="col-12"><label class="form-label">Título</label><input type="text" class="form-control" name="section_title" value="'.($d['section_title']??'').'"></div>
                    <div class="col-12"><label class="form-label">Origem</label><select class="form-select" name="faq_origin">
                        <option value="auto" '.($d['faq_origin']??''=='auto'?'selected':'').'>Automático (do banco)</option>
                        <option value="manual" '.($d['faq_origin']??''=='manual'?'selected':'').'>Manual (JSON)</option>
                    </select></div>
                </div>';

            case 'cta':
                return '<div class="row g-3">
                    <div class="col-12"><label class="form-label">Texto</label><input type="text" class="form-control" name="cta_text" value="'.($d['cta_text']??'').'"></div>
                    <div class="col-12"><label class="form-label">Subtexto</label><textarea class="form-control" name="cta_subtext" rows="2">'.($d['cta_subtext']??'').'</textarea></div>
                    <div class="col-md-4"><label class="form-label">Botão</label><input type="text" class="form-control" name="cta_button_text" value="'.($d['cta_button_text']??'').'"></div>
                    <div class="col-md-4"><label class="form-label">URL</label><input type="text" class="form-control" name="cta_button_url" value="'.($d['cta_button_url']??'').'"></div>
                    <div class="col-md-4"><label class="form-label">Cor Fundo</label><input type="color" class="form-control form-control-color" name="cta_bg_color" value="'.($d['cta_bg_color']??'#6C5CE7').'"></div>
                </div>';

            case 'testimonials':
                return '<div class="row g-3">
                    <div class="col-12"><label class="form-label">Título</label><input type="text" class="form-control" name="section_title" value="'.($d['section_title']??'').'"></div>
                    <div class="col-12"><label class="form-label">Depoimentos (JSON)</label><textarea class="form-control" name="testimonials" rows="6">'.json_encode($d['testimonials']??[], JSON_UNESCAPED_UNICODE).'</textarea></div>
                </div>';

            case 'footer':
                return '<div class="row g-3">
                    <div class="col-12"><label class="form-label">Descrição</label><textarea class="form-control" name="footer_description" rows="2">'.($d['footer_description']??'').'</textarea></div>
                    <div class="col-12"><label class="form-label">Links (JSON)</label><textarea class="form-control" name="footer_links" rows="4">'.json_encode($d['footer_links']??[], JSON_UNESCAPED_UNICODE).'</textarea></div>
                    <div class="col-12"><label class="form-label">Copyright</label><input type="text" class="form-control" name="copyright" value="'.($d['copyright']??'').'"></div>
                </div>';

            case 'custom_html':
                return '<div class="row g-3">
                    <div class="col-12"><label class="form-label">Título Interno</label><input type="text" class="form-control" name="section_title" value="'.($d['section_title']??'').'"></div>
                    <div class="col-12"><label class="form-label">HTML</label><textarea class="form-control" name="custom_html" rows="8">'.($d['custom_html']??'').'</textarea></div>
                    <div class="col-12"><label class="form-label">CSS</label><textarea class="form-control" name="custom_css" rows="4">'.($d['custom_css']??'').'</textarea></div>
                </div>';

            default:
                return '<p class="text-muted">Bloco não implementado</p>';
        }
    }
    private function getAvailableBlocks()
    {
        return [
            'hero' => [
                'name' => 'Hero (Banner)',
                'icon' => 'fas fa-image',
                'desc' => 'Seção principal de apresentação',
            ],
            'features' => [
                'name' => 'Recursos',
                'icon' => 'fas fa-th-large',
                'desc' => 'Grade de funcionalidades',
            ],
            'pricing' => [
                'name' => 'Preços',
                'icon' => 'fas fa-tags',
                'desc' => 'Tabela de planos e preços',
            ],
            'faq' => [
                'name' => 'FAQ',
                'icon' => 'fas fa-question-circle',
                'desc' => 'Perguntas frequentes',
            ],
            'testimonials' => [
                'name' => 'Depoimentos',
                'icon' => 'fas fa-star',
                'desc' => 'Prova social de clientes',
            ],
            'cta' => [
                'name' => 'CTA',
                'icon' => 'fas fa-bullhorn',
                'desc' => 'Call-to-action de conversão',
            ],
            'footer' => [
                'name' => 'Rodapé',
                'icon' => 'fas fa-window-minimize',
                'desc' => 'Rodapé da página',
            ],
            'custom_html' => [
                'name' => 'HTML Livre',
                'icon' => 'fas fa-code',
                'desc' => 'Bloco HTML personalizado',
            ],
        ];
    }
}
