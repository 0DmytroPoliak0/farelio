-- db/seed.sql
USE travel_app;

-- Destinations
INSERT INTO destinations (code, city, country) VALUES
('YVR', 'Vancouver', 'Canada'),
('FCO', 'Rome', 'Italy'),
('CDG', 'Paris', 'France');

-- Hotels in Rome (FCO)
INSERT INTO hotels (destination_code, name, rating, distance_to_center_km, nightly_price) VALUES
('FCO', 'Budget Inn Roma', 7.3, 4.5, 85.00),
('FCO', 'Centro City Hotel', 8.1, 1.2, 120.00),
('FCO', 'Colosseum View Stay', 9.0, 0.8, 160.00);

-- Sample flights YVR -> FCO with different prices / durations
INSERT INTO flights (origin_code, destination_code, depart_date, return_date,
                     airline, stops, total_duration_min, price, bag_included)
VALUES
('YVR', 'FCO', '2026-02-10', '2026-02-14', 'StudentAir', 1, 900, 850.00, 0),
('YVR', 'FCO', '2026-02-10', '2026-02-14', 'BudgetWings', 2, 1100, 730.00, 0),
('YVR', 'FCO', '2026-02-11', '2026-02-15', 'ComfortFly', 1, 880, 910.00, 1),
('YVR', 'FCO', '2026-02-12', '2026-02-16', 'QuickJet', 0, 760, 1100.00, 1);