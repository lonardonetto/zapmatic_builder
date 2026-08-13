<?php
declare(strict_types=1);

// Bootstrap dos testes PHPUnit do projeto.
// Registra os autoloaders PSR-4 das bibliotecas sob teste e carrega o
// printer TAP. Não depende do bootstrap do CodeIgniter: as bibliotecas de
// telefone/filtro/fila são puras (zero acoplamento).

spl_autoload_register(static function (string $class): void {
    // Core\Whatsapp_export_participants\Libraries\Foo -> inc/core/Whatsapp_export_participants/Libraries/Foo.php
    $prefix = 'Core\\Whatsapp_export_participants\\';
    if (strncmp($class, $prefix, strlen($prefix)) === 0) {
        $rel = substr($class, strlen($prefix));
        $file = dirname(__DIR__) . '/inc/core/Whatsapp_export_participants/' . str_replace('\\', '/', $rel) . '.php';
        if (is_file($file)) {
            require $file;
        }
        return;
    }
});
