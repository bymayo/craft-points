<?php

namespace bymayo\points\conditions\rules\commerce;

use bymayo\points\conditions\BaseConditionRule;
use bymayo\points\conditions\RuleEvaluationContext;
use Craft;
use craft\helpers\Cp;

/**
 * Passes if any of the order's line items references one of the configured product IDs.
 *
 * Config: { productIds: "1, 5, 12" } — comma-separated list of product IDs.
 *
 * Note: line items reference *purchasables* (usually variants). We resolve each
 * line item's variant → product ID for the check.
 */
class OrderContainsProductConditionRule extends BaseConditionRule
{
    public function handle(): string { return 'commerce.containsProduct'; }
    public function label(): string { return 'Contains product(s)'; }
    public function group(): string { return 'Commerce'; }
    public function appliesToSubjects(): ?array { return ['order']; }

    public function evaluate(array $config, RuleEvaluationContext $ctx): bool
    {
        $raw = $config['productIds'] ?? [];
        $wanted = is_array($raw)
            ? array_values(array_filter(array_map('intval', $raw)))
            : array_values(array_filter(array_map(
                fn($s) => (int) trim((string) $s),
                explode(',', (string) $raw),
            )));
        if (empty($wanted)) {
            return true;
        }

        $sender = $ctx->triggerEvent?->sender ?? null;
        $order = $sender && method_exists($sender, 'getLineItems')
            ? $sender
            : ($ctx->triggerEvent?->transaction?->order ?? null);

        if (!$order || !method_exists($order, 'getLineItems')) {
            return false;
        }

        foreach ($order->getLineItems() as $item) {
            $purchasable = $item->getPurchasable();
            if (!$purchasable) {
                continue;
            }
            // Variants expose ->productId; non-variant purchasables may expose ->id.
            $productId = $purchasable->productId ?? $purchasable->id ?? null;
            if ($productId !== null && in_array((int) $productId, $wanted, true)) {
                return true;
            }
        }
        return false;
    }

    public function renderConfigUi(int $index, array $config): string
    {
        return Cp::elementSelectHtml([
            'name' => "conditions[{$index}][productIds]",
            'elementType' => 'craft\\commerce\\elements\\Product',
            'elements' => $this->lookupSelected($config['productIds'] ?? []),
            'limit' => null,
            'selectionLabel' => Craft::t('points', 'Add products'),
        ]);
    }

    /**
     * Resolve saved product IDs (array or comma-separated string) into the
     * element objects `Cp::elementSelectHtml` needs to render the initial
     * selection.
     *
     * @param mixed $raw
     * @return list<\craft\base\ElementInterface>
     */
    private function lookupSelected(mixed $raw): array
    {
        if (!class_exists('craft\\commerce\\elements\\Product')) {
            return [];
        }
        $ids = is_array($raw)
            ? array_map('intval', $raw)
            : array_filter(array_map(
                fn($s) => (int) trim($s),
                explode(',', (string) $raw),
            ));
        $ids = array_values(array_filter($ids));
        if (!$ids) {
            return [];
        }
        return \craft\commerce\elements\Product::find()->id($ids)->all();
    }
}
