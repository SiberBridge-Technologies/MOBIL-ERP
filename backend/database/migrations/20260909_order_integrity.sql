-- Apply once to existing installations; fresh installations use schema.sql.
ALTER TABLE orders ADD COLUMN request_id VARCHAR(80) NULL, ADD COLUMN request_hash CHAR(64) NULL, ADD UNIQUE KEY employee_request (employee_id, request_id);
ALTER TABLE order_items MODIFY koli_adedi DECIMAL(16,6) NOT NULL DEFAULT 0, ADD COLUMN kdv_orani DECIMAL(5,2) NULL, ADD COLUMN kdv_tutari DECIMAL(14,2) NULL;
-- Historical tax values stay NULL: their original per-line rates cannot be inferred safely.
