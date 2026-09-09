<?php
/**
 * Carousel Widget Test Suite
 * 
 * Testes TDD para validar que os widgets do carrossel (widget_menu e
 * widget_content) estao presentes em TODAS as paginas de envio de mensagem.
 * 
 * @feature carrossel-correcao
 * @spec:AC-106 @spec:AC-107 @spec:AC-108
 */

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class CarouselWidgetTest extends TestCase
{
    private static string $corePath;

    public static function setUpBeforeClass(): void
    {
        self::$corePath = dirname(__DIR__, 2) . '/inc/core';
    }

    // ─────────────────────────────────────────────────────────────────
    // Helper: verifica se um arquivo contém widget_menu E widget_content do carrossel
    // ─────────────────────────────────────────────────────────────────

    private function assertHasCarouselWidget(string $filePath, string $acId): void
    {
        $this->assertFileExists($filePath, "{$acId}: Arquivo nao encontrado: {$filePath}");

        $content = file_get_contents($filePath);

        $this->assertMatchesRegularExpression(
            '/Whatsapp_carousel_template.*widget_menu/s',
            $content,
            "{$acId}: widget_menu do carrossel ausente em {$filePath}"
        );

        $this->assertMatchesRegularExpression(
            '/Whatsapp_carousel_template.*widget_content/s',
            $content,
            "{$acId}: widget_content do carrossel ausente em {$filePath}"
        );
    }

    private function assertHasPollWidget(string $filePath, string $acId): void
    {
        $content = file_get_contents($filePath);

        $this->assertMatchesRegularExpression(
            '/Whatsapp_poll_template.*widget_menu/s',
            $content,
            "{$acId}: widget_menu da enquete ausente em {$filePath}"
        );

        $this->assertMatchesRegularExpression(
            '/Whatsapp_poll_template.*widget_content/s',
            $content,
            "{$acId}: widget_content da enquete ausente em {$filePath}"
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // AC-106: Callresponder tem aba de carrossel
    // ─────────────────────────────────────────────────────────────────

    /**
     * @spec:AC-106
     * Dado que estou na pagina de criacao de chatbot (callresponder)
     * Quando a pagina carrega
     * Entao a aba "Carousel" aparece com widget_menu e widget_content
     */
    public function testCallresponderHasCarouselWidget_AC106_at_spec_AC106(): void
    {
        $this->assertHasCarouselWidget(
            self::$corePath . '/Whatsapp_callresponder/Views/info.php',
            'AC-106'
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // AC-107: API REST tem aba de carrossel e enquete
    // ─────────────────────────────────────────────────────────────────

    /**
     * @spec:AC-107
     * Dado que estou na pagina de configuracao de API REST
     * Quando a pagina carrega
     * Entao as abas "Enquete" e "Carousel" aparecem
     */
    public function testApiRestHasCarouselWidget_AC107_at_spec_AC107(): void
    {
        $this->assertHasCarouselWidget(
            self::$corePath . '/Whatsapp_api/Views/info.php',
            'AC-107'
        );
    }

    /**
     * @spec:AC-107
     * A pagina de API REST tambem deve ter a aba de enquete
     */
    public function testApiRestHasPollWidget_AC107_at_spec_AC107(): void
    {
        $this->assertHasPollWidget(
            self::$corePath . '/Whatsapp_api/Views/info.php',
            'AC-107'
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // AC-108: Criptografia tem aba de carrossel e enquete
    // ─────────────────────────────────────────────────────────────────

    /**
     * @spec:AC-108
     * Dado que estou na pagina de criptografia de textos
     * Quando a pagina carrega
     * Entao as abas "Enquete" e "Carousel" aparecem
     */
    public function testCriptografiaHasCarouselWidget_AC108_at_spec_AC108(): void
    {
        $this->assertHasCarouselWidget(
            self::$corePath . '/Criptografia_copy/Views/info.php',
            'AC-108'
        );
    }

    /**
     * @spec:AC-108
     * A pagina de criptografia tambem deve ter a aba de enquete
     */
    public function testCriptografiaHasPollWidget_AC108_at_spec_AC108(): void
    {
        $this->assertHasPollWidget(
            self::$corePath . '/Criptografia_copy/Views/info.php',
            'AC-108'
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // Regressao: paginas que JÁ tinham o widget nao foram quebradas
    // ─────────────────────────────────────────────────────────────────

    /**
     * Bulk messaging DEVE continuar tendo carousel widget
     */
    public function testBulkStillHasCarouselWidget(): void
    {
        $this->assertHasCarouselWidget(
            self::$corePath . '/Whatsapp_bulk/Views/update.php',
            'regressao-bulk'
        );
    }

    /**
     * Send message DEVE continuar tendo carousel widget
     */
    public function testSendMessageStillHasCarouselWidget(): void
    {
        $this->assertHasCarouselWidget(
            self::$corePath . '/Whatsapp_send_message/Views/info.php',
            'regressao-send_message'
        );
    }

    // ─────────────────────────────────────────────────────────────────
    // Widget views existem
    // ─────────────────────────────────────────────────────────────────

    /**
     * O widget menu do carrossel DEVE existir
     */
    public function testCarouselWidgetMenuViewExists(): void
    {
        $this->assertFileExists(
            self::$corePath . '/Whatsapp_carousel_template/Views/widget/menu.php',
            'View widget/menu.php do carrossel nao encontrada'
        );
    }

    /**
     * O widget content do carrossel DEVE existir
     */
    public function testCarouselWidgetContentViewExists(): void
    {
        $this->assertFileExists(
            self::$corePath . '/Whatsapp_carousel_template/Views/widget/content.php',
            'View widget/content.php do carrossel nao encontrada'
        );
    }

    /**
     * O widget content DEVE filtrar por type=5
     */
    public function testCarouselWidgetContentFiltersType5(): void
    {
        $file = self::$corePath . '/Whatsapp_carousel_template/Controllers/Whatsapp_carousel_template.php';
        $content = file_get_contents($file);

        $this->assertMatchesRegularExpression(
            '/widget_content.*type.*5/s',
            $content,
            'widget_content do carrossel nao filtra por type=5 no controller'
        );
    }
}
