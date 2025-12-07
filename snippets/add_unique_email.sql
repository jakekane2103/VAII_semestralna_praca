-- Add unique constraint on zakaznik.email to enforce uniqueness at the database level
-- Run this once against your database (adjust schema/table/column names if needed)

-- MySQL
ALTER TABLE zakaznik
  ADD CONSTRAINT uq_zakaznik_email UNIQUE (email);

-- PostgreSQL equivalent (if you're using Postgres):
-- ALTER TABLE zakaznik
--   ADD CONSTRAINT uq_zakaznik_email UNIQUE (email);

-- Note: If there are existing duplicate emails, the ALTER TABLE will fail; you should clean duplicates first.

