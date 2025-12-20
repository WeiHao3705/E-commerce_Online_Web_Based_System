<?php

class InventoryRepository {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function getInventoryByKey($productId, $variantId, $size) {
        $sql = 'SELECT * FROM inventory WHERE size = :size AND ';
        if ($variantId !== null) {
            $sql .= 'variant_id = :vid';
        } else {
            $sql .= 'variant_id IS NULL';
        }
        $sql .= ' AND product_id ' . ($productId !== null ? '= :pid' : 'IS NULL');

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':size', $size);
        if ($variantId !== null) {
            $stmt->bindValue(':vid', (int)$variantId, PDO::PARAM_INT);
        }
        if ($productId !== null) {
            $stmt->bindValue(':pid', (int)$productId, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function upsertInventory($productId, $variantId, $size, $deltaQty) {
        // Try update existing row
        $updateSql = 'UPDATE inventory SET stock_quantity = stock_quantity + :qty WHERE size = :size AND ';
        if ($variantId !== null) {
            $updateSql .= 'variant_id = :vid';
        } else {
            $updateSql .= 'variant_id IS NULL';
        }
        $updateSql .= ' AND product_id ' . ($productId !== null ? '= :pid' : 'IS NULL');

        $stmt = $this->conn->prepare($updateSql);
        $stmt->bindValue(':qty', (int)$deltaQty, PDO::PARAM_INT);
        $stmt->bindValue(':size', $size);
        if ($variantId !== null) {
            $stmt->bindValue(':vid', (int)$variantId, PDO::PARAM_INT);
        }
        if ($productId !== null) {
            $stmt->bindValue(':pid', (int)$productId, PDO::PARAM_INT);
        }
        $stmt->execute();
        if ($stmt->rowCount() > 0) {
            return true;
        }

        // Insert new row
        $insertSql = 'INSERT INTO inventory (product_id, variant_id, size, stock_quantity) VALUES (:pid, :vid, :size, :qty)';
        $stmt = $this->conn->prepare($insertSql);
        $stmt->bindValue(':pid', $productId !== null ? (int)$productId : null, $productId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':vid', $variantId !== null ? (int)$variantId : null, $variantId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':size', $size);
        $stmt->bindValue(':qty', (int)$deltaQty, PDO::PARAM_INT);
        return $stmt->execute();
    }

    public function getProductsBasic() {
        $stmt = $this->conn->query('SELECT product_id, product_name FROM product ORDER BY product_name');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getVariantsByProduct($productId) {
        $stmt = $this->conn->prepare('SELECT variant_id, color FROM product_variant WHERE product_id = :pid ORDER BY color');
        $stmt->bindValue(':pid', (int)$productId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSizesForProduct($productId) {
        $stmt = $this->conn->prepare('SELECT DISTINCT size FROM inventory WHERE product_id = :pid AND variant_id IS NULL ORDER BY size');
        $stmt->bindValue(':pid', (int)$productId, PDO::PARAM_INT);
        $stmt->execute();
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'size');
    }

    public function getSizesForVariant($variantId) {
        $stmt = $this->conn->prepare('SELECT DISTINCT size FROM inventory WHERE variant_id = :vid ORDER BY size');
        $stmt->bindValue(':vid', (int)$variantId, PDO::PARAM_INT);
        $stmt->execute();
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'size');
    }
}
