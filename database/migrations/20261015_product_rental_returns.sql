-- Apply once. The PHP migration runner safely skips columns that already exist.
ALTER TABLE product_sales
    ADD COLUMN transaction_type ENUM('sale','rental') NULL DEFAULT NULL,
    ADD COLUMN rental_status ENUM('rented','returned') NULL DEFAULT NULL,
    ADD COLUMN rental_stock_deducted TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN returned_at DATETIME NULL DEFAULT NULL,
    ADD COLUMN returned_by_user_id INT NULL DEFAULT NULL;

-- Older rentals never deducted stock. Track them without changing inventory.
UPDATE product_sales s JOIN products p ON p.id = s.product_id
SET s.transaction_type = p.type,
    s.rental_status = CASE WHEN p.type = 'rental' THEN 'rented' ELSE NULL END
WHERE s.transaction_type IS NULL;
