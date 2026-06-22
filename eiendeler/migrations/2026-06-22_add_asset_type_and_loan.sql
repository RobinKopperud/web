-- Legg til valgfri under-type og enkel lånesaldo for eksisterende eiendeler.
ALTER TABLE assets
    ADD COLUMN asset_type VARCHAR(80) NULL AFTER category,
    ADD COLUMN loan_amount DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER gross_value;
