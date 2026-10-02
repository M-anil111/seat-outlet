-- Price-drop alerts: an interest on an event can ask to be told when its lowest price falls.
--   alert_kind       'dates' (default: performer dates / tickets listed) or 'price' (price-drop alert on one event)
--   baseline_price   the lowest price the visitor saw when they signed up; an alert goes out when the price is at least 10% below it,
--                    and the baseline then moves to the price that was mailed so the next alert needs another drop
ALTER TABLE `lead_interests` ADD COLUMN `alert_kind` VARCHAR(8) NOT NULL DEFAULT 'dates';
ALTER TABLE `lead_interests` ADD COLUMN `baseline_price` DECIMAL(10,2) NULL;
