<?php
namespace Core\Whatsapp_export_participants\Libraries;

/**
 * GroupPaginator
 *
 * Paginação pura de listas de grupos. Isolado de propósito (zero acoplamento
 * com CodeIgniter), seguindo a mesma filosofia de PhoneNormalizer/PhoneValidator.
 */
class GroupPaginator
{
    public const DEFAULT_LIMIT = 50;

    /**
     * Devolve a fatia da página pedida e o total de itens.
     *
     * @param array $items Lista completa (qualquer array indexado).
     * @param int   $page  Página 1-based.
     * @param int   $limit Tamanho da página.
     * @return array{items: array, total: int, page: int, limit: int}
     */
    public static function paginate(array $items, int $page, int $limit): array
    {
        $limit = self::normalizeLimit($limit);
        $total = count($items);

        if ($page < 1) {
            return ['items' => [], 'total' => $total, 'page' => $page, 'limit' => $limit];
        }

        $offset = ($page - 1) * $limit;
        if ($offset >= $total) {
            return ['items' => [], 'total' => $total, 'page' => $page, 'limit' => $limit];
        }

        return [
            'items' => array_slice(array_values($items), $offset, $limit),
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ];
    }

    /**
     * Normaliza o limite: valores não-positivos caem no padrão.
     */
    public static function normalizeLimit(int $limit): int
    {
        return $limit > 0 ? $limit : self::DEFAULT_LIMIT;
    }
}
