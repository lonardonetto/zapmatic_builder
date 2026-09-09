<?php
/**
 * Carousel Routing Test Suite
 * 
 * Testes TDD para validar que templates de carrossel (type=5) sao
 * roteados corretamente e nao misturados com templates de botao (type=2).
 * 
 * @feature carrossel-correcao
 * @spec:AC-095 @spec:AC-096 @spec:AC-097 @spec:AC-100 @spec:AC-105
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class CarouselRoutingTest extends TestCase
{
    private static string $routesFile;
    private static string $routesContent;

    public static function setUpBeforeClass(): void
    {
        self::$routesFile = dirname(__DIR__) . '/app/Config/Routes.php';
        self::$routesContent = file_get_contents(self::$routesFile);
    }

    // ─────────────────────────────────────────────────────────────────
    // AC-097: Rota explicita direciona save para controller correto
    // ─────────────────────────────────────────────────────────────────

    /**
     * @spec:AC-097
     * Dado que o formulario de carrossel gera action URL
     *       /whatsapp_carousel_template/save/
     * Quando o POST e enviado
     * Entao o framework executa Whatsapp_carousel_template::save()
     *       e nao Whatsapp_button_template::save()
     */
    public function testRoutesExplicitSaveRouteExists(): void
    {
        $this->assertStringContainsString(
            'whatsapp_carousel_template/save',
            self::$routesContent,
            'AC-097: Rota explicita para /whatsapp_carousel_template/save nao encontrada em Routes.php'
        );
    }

    /**
     * @spec:AC-097
     * A rota save deve apontar para o controller de carrossel, nao de botao
     */
    public function testSaveRoutePointsToCarouselController(): void
    {
        $this->assertMatchesRegularExpression(
            '/whatsapp_carousel_template\/save.*Whatsapp_carousel_template.*save/s',
            self::$routesContent,
            'AC-097: Rota save nao aponta para Core\\Whatsapp_carousel_template\\Controllers\\Whatsapp_carousel_template::save'
        );
    }

    /**
     * @spec:AC-097
     * A rota save com parametro deve aceitar IDs de template
     */
    public function testSaveRouteWithIdParameter(): void
    {
        $this->assertMatchesRegularExpression(
            '/whatsapp_carousel_template\/save\/\(:any\)/',
            self::$routesContent,
            'AC-097: Rota save com parametro (:any) nao encontrada'
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // AC-095: Template de carrossel salva com type=5
    // ─────────────────────────────────────────────────────────────────

    /**
     * @spec:AC-095
     * O controller de carrossel DEVE salvar com type=5
     */
    public function testCarouselControllerSaveUsesType5(): void
    {
        $controllerFile = dirname(__DIR__) . '/inc/core/Whatsapp_carousel_template/Controllers/Whatsapp_carousel_template.php';
        $content = file_get_contents($controllerFile);

        $this->assertMatchesRegularExpression(
            '/[\'"]type[\'"]\s*=>\s*5/',
            $content,
            'AC-095: Controller de carrossel nao salva com type=5 no db_insert'
        );
    }

    /**
     * @spec:AC-095
     * O controller de botao DEVE salvar com type=2 (nao interferir)
     */
    public function testButtonControllerSaveUsesType2(): void
    {
        $controllerFile = dirname(__DIR__) . '/inc/core/Whatsapp_button_template/Controllers/Whatsapp_button_template.php';
        $content = file_get_contents($controllerFile);

        $this->assertMatchesRegularExpression(
            '/[\'"]type[\'"]\s*=>\s*2/',
            $content,
            'AC-095: Controller de botao nao salva com type=2 (pode causar conflito com carrossel)'
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // AC-096: Template de carrossel NAO aparece na aba de Botoes
    // ─────────────────────────────────────────────────────────────────

    /**
     * @spec:AC-096
     * O model de botao DEVE filtrar exclusivamente por type=2
     */
    public function testButtonModelFiltersOnlyType2(): void
    {
        $modelFile = dirname(__DIR__) . '/inc/core/Whatsapp_button_template/Models/Whatsapp_button_templateModel.php';
        $content = file_get_contents($modelFile);

        $this->assertMatchesRegularExpression(
            '/type\s*=\s*2/',
            $content,
            'AC-096: Model de botao nao filtra por type=2 — templates de carrossel podem vazar'
        );
    }

    /**
     * @spec:AC-096
     * O model de carrossel DEVE filtrar exclusivamente por type=5
     */
    public function testCarouselModelFiltersOnlyType5(): void
    {
        $modelFile = dirname(__DIR__) . '/inc/core/Whatsapp_carousel_template/Models/Whatsapp_carousel_templateModel.php';
        $content = file_get_contents($modelFile);

        $this->assertMatchesRegularExpression(
            '/type\s*=\s*5/',
            $content,
            'AC-096: Model de carrossel nao filtra por type=5'
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // AC-100: Endpoint ajax_list do carrossel retorna somente type=5
    // ─────────────────────────────────────────────────────────────────

    /**
     * @spec:AC-100
     * Rota explicita para ajax_list do carrossel existe
     */
    public function testAjaxListRouteExists(): void
    {
        $this->assertStringContainsString(
            'whatsapp_carousel_template/ajax_list',
            self::$routesContent,
            'AC-100: Rota explicita para /whatsapp_carousel_template/ajax_list nao encontrada'
        );
    }

    /**
     * @spec:AC-100
     * A rota ajax_list aponta para o controller de carrossel
     */
    public function testAjaxListRoutePointsToCarouselController(): void
    {
        $this->assertMatchesRegularExpression(
            '/whatsapp_carousel_template\/ajax_list.*Whatsapp_carousel_template.*ajax_list/s',
            self::$routesContent,
            'AC-100: Rota ajax_list nao aponta para o controller de carrossel'
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // AC-105: Dados corrigidos (template com cards mas type errado)
    // ─────────────────────────────────────────────────────────────────

    /**
     * @spec:AC-105
     * O Config do carrossel DEVE ter id unico whatsapp_carousel_template
     */
    public function testCarouselConfigHasUniqueId(): void
    {
        $configFile = dirname(__DIR__) . '/inc/core/Whatsapp_carousel_template/Config.php';
        $config = include $configFile;

        $this->assertEquals('whatsapp_carousel_template', $config['id'],
            'AC-105: Config do carrossel nao tem id=whatsapp_carousel_template');
    }

    /**
     * @spec:AC-105
     * O Config do carrossel DEVE ter parent.id = template
     */
    public function testCarouselConfigHasCorrectParent(): void
    {
        $configFile = dirname(__DIR__) . '/inc/core/Whatsapp_carousel_template/Config.php';
        $config = include $configFile;

        $this->assertEquals('template', $config['parent']['id'],
            'AC-105: Config do carrossel nao tem parent.id=template');
    }

    /**
     * @spec:AC-105
     * O Config do carrossel DEVE ter menu.sub_menu.id para routing
     */
    public function testCarouselConfigHasSubMenuId(): void
    {
        $configFile = dirname(__DIR__) . '/inc/core/Whatsapp_carousel_template/Config.php';
        $config = include $configFile;

        $this->assertArrayHasKey('menu', $config,
            'AC-105: Config do carrossel nao tem chave menu');
        $this->assertArrayHasKey('sub_menu', $config['menu'],
            'AC-105: Config do carrossel nao tem menu.sub_menu');
        $this->assertEquals('whatsapp_carousel_template', $config['menu']['sub_menu']['id'],
            'AC-105: menu.sub_menu.id difere do id do modulo');
    }

    // ─────────────────────────────────────────────────────────────────
    // Rotas de index e delete
    // ─────────────────────────────────────────────────────────────────

    /**
     * @spec:AC-097
     * Rota explicita para index do carrossel existe
     */
    public function testIndexRouteExists(): void
    {
        $this->assertStringContainsString(
            'whatsapp_carousel_template/index',
            self::$routesContent,
            'Rota explicita para /whatsapp_carousel_template/index nao encontrada'
        );
    }

    /**
     * @spec:AC-097
     * Rota explicita para delete do carrossel existe
     */
    public function testDeleteRouteExists(): void
    {
        $this->assertStringContainsString(
            'whatsapp_carousel_template/delete',
            self::$routesContent,
            'Rota explicita para /whatsapp_carousel_template/delete nao encontrada'
        );
    }
}
