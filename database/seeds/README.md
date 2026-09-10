# Database Seeds

This directory contains PHP scripts or SQL files used to insert initial seed data into the database.

## Usage
Seeders are typically used for:
1. **Admin/Roles:** Initializing the `roles` and `permissions` tables, and creating the default Super Admin user.
2. **Categories:** Setting up initial `product_categories`, `article_categories`, and `health_conditions`.
3. **Demo Data:** Populating dummy products, programs, and articles for development environments.

When writing seeders, ensure they check if data already exists to avoid duplicate entry errors on multiple runs.
