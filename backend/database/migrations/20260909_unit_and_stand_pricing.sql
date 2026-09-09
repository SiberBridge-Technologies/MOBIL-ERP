ALTER TABLE products
  ADD COLUMN stand_aktif TINYINT(1) NOT NULL DEFAULT 0 AFTER koli_ici_adet,
  ADD COLUMN stand_ici_adet INT NULL AFTER stand_aktif,
  ADD COLUMN stand_fiyati DECIMAL(12,2) NULL AFTER stand_ici_adet;

-- Eski dip fiyatları koli tabanlıydı; iş kuralını koruyarak adet fiyatına çevir.
UPDATE products
SET dip_fiyat = ROUND(dip_fiyat / GREATEST(koli_ici_adet, 1), 2),
    koli_fiyati = ROUND(liste_fiyati * GREATEST(koli_ici_adet, 1), 2),
    stand_aktif = 0,
    stand_ici_adet = NULL,
    stand_fiyati = NULL;
