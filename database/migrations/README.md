# Database Migrations

This directory contains PHP scripts or SQL files used to manage incremental database schema changes over time.

## Usage
Since `schema.sql` at the root of `database/` acts as the master setup for a fresh installation, this folder should be used for:
1. Adding new columns to existing tables in the future.
2. Creating new tables as features are built.
3. Modifying constraints or indexes.

Migrations should be run sequentially based on timestamps (e.g., `2026_09_09_001_add_status_to_users.sql`).
