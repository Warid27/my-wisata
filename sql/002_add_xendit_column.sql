-- Add Xendit invoice ID column to orders table
ALTER TABLE orders ADD COLUMN xendit_invoice_id VARCHAR(100) NULL AFTER id_voucher;

-- Add index for faster lookup
CREATE INDEX idx_orders_xendit_invoice ON orders(xendit_invoice_id);
