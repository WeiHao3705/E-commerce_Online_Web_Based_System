<?php

require_once __DIR__ . '/../repository/InventoryRepository.php';

class InventoryService {
    private $repo;
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->repo = new InventoryRepository($conn);
    }

    public function getRestockData() {
        $products = $this->repo->getProductsBasic();
        $variantsMap = [];
        $sizesByProduct = [];
        $sizesByVariant = [];
        foreach ($products as $p) {
            $pid = (int)$p['product_id'];
            $variants = $this->repo->getVariantsByProduct($pid);
            $variantsMap[$pid] = $variants;
            $sizesByProduct[$pid] = $this->repo->getSizesForProduct($pid);
            foreach ($variants as $v) {
                $vid = (int)$v['variant_id'];
                $sizesByVariant[$vid] = $this->repo->getSizesForVariant($vid);
            }
        }
        return [
            'products' => $products,
            'variantsMap' => $variantsMap,
            'sizesByProduct' => $sizesByProduct,
            'sizesByVariant' => $sizesByVariant,
        ];
    }

    public function restock($productId, $variantId, $size, $quantity) {
        if (!is_numeric($quantity) || (int)$quantity <= 0) {
            throw new Exception('Quantity must be a positive integer.');
        }
        $productId = $productId !== null ? (int)$productId : null;
        $variantId = $variantId !== null && $variantId !== '' ? (int)$variantId : null;
        $size = trim($size);
        if ($size === '') {
            throw new Exception('Size is required.');
        }

        $this->conn->beginTransaction();
        try {
            $this->repo->upsertInventory($productId, $variantId, $size, (int)$quantity);
            $this->conn->commit();
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw $e;
        }
    }
}
