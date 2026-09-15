-- Insert Categories
INSERT IGNORE INTO product_categories (id, name, slug) VALUES 
(1, 'Atta', 'atta'),
(2, 'Cookies & Snacks', 'cookies'),
(3, 'Supplements', 'supplements'),
(4, 'Health Foods', 'health');

-- Insert Products
INSERT IGNORE INTO products (id, category_id, name, slug, description, price, stock, sku, thumbnail_url, is_active) VALUES
(1, 1, 'NutroActive Keto Atta 750g with Keto Sugar 250g Combo', 'nutroactive-keto-atta-750g-with-keto-sugar-250g-combo', 'A healthier choice for your everyday meals. Low carb, high protein keto flours for your daily needs.', 999.00, 100, 'N-KETO-ATTA-COMBO', 'https://cdn.shopify.com/s/files/1/0688/6562/2325/files/ketoatta_sugarcopy.jpg?v=1753341833', 1),
(2, 4, 'Diabexy Nutrition Combo (Reversol, Basics, Anteflame)', 'diabexy-nutrition-combo', 'Comprehensive nutrition combo for managing health and maintaining sugar levels.', 1499.00, 50, 'D-NUTRI-COMBO', 'https://cdn.shopify.com/s/files/1/0688/6562/2325/files/Diabexy_combo_with_minibreakfastbar.jpg?v=1785315372', 1),
(3, 1, 'Diabexy Atta Sugar Control (500g)', 'diabexy-atta-sugar-control-500g', 'Specially formulated atta to help control sugar levels naturally without compromising on taste.', 299.00, 0, 'D-ATTA-500G', 'https://cdn.shopify.com/s/files/1/0688/6562/2325/files/Diabexy_Atta_SugarControl_500g.jpg?v=1785315383', 1),
(4, 3, 'Nutroactive Lipolyzer 30 Tablets', 'nutroactive-lipolyzer-30-tablets', 'High-quality herbal supplement to support a healthy and active lifestyle.', 1094.00, 200, 'N-LIPO-30', 'https://images.unsplash.com/photo-1550989460-0adf9ea622e2?auto=format&fit=crop&w=800&q=80', 1);

-- Insert Product Images
INSERT IGNORE INTO product_images (product_id, image_url, is_primary) VALUES
(1, 'https://cdn.shopify.com/s/files/1/0688/6562/2325/files/ketoatta_sugarcopy.jpg?v=1753341833', 1),
(2, 'https://cdn.shopify.com/s/files/1/0688/6562/2325/files/Diabexy_combo_with_minibreakfastbar.jpg?v=1785315372', 1),
(3, 'https://cdn.shopify.com/s/files/1/0688/6562/2325/files/Diabexy_Atta_SugarControl_500g.jpg?v=1785315383', 1),
(4, 'https://images.unsplash.com/photo-1550989460-0adf9ea622e2?auto=format&fit=crop&w=800&q=80', 1);
