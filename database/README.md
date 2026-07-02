# RideEase – Database Documentation

The RideEase database is built on **MySQL** and configured for XAMPP.

---

## 🗄️ Database Import Instructions

1. Start **XAMPP Control Panel**.
2. Click **Start** on Apache and MySQL modules.
3. Open browser and go to `http://localhost/phpmyadmin/`.
4. Click **New** in the sidebar, name the database `rideease_db`, and select collation `utf8mb4_unicode_ci`.
5. Select `rideease_db` database, click **Import** tab on the top menu.
6. Click **Choose File** and locate `database/rideease.sql` from your project folder.
7. Click **Import** at the bottom.

---

## 📐 Table Relationships & Fields

- **users**: Stores passenger, driver, and admin profiles. The `password_hash` column holds password credentials hashed via standard PHP Bcrypt.
- **drivers**: Links to `users` table via one-to-one foreign key mapping (`user_id`). Stores license details and ratings.
- **vehicles**: Links to `drivers` table (`driver_id`). Contains make, model, type, registration plate, and verification state.
- **rides**: Connects passenger (`passenger_id`) and driver (`driver_id`). Tracks ride status (`pending`, `assigned`, `on_ride`, `completed`, `cancelled`) and payment methods.
- **cancellations**: One-to-one mapping to `rides`. Stores cancel logs and passenger/driver/admin text reasons.
- **payments**: Tracks payment channels (bkash, card, cash) and simulated transaction codes.
- **driver_earnings**: Stores driver payout shares (80%) and platform commission fees (20%).
- **coupons**: Tracks promotional coupon code states, expirations, discount values, and limits.
- **ratings**: Tracks star review data (1-5) and text feedback for completed rides.
- **sos_alerts**: Logs active emergency distress alerts. Accessible by administrators in real-time.
- **complaints**: User ticket logs. Allows admin responses.
- **favorite_locations**: Saved locations for quick auto-fill of route forms.
- **peak_hours**: Time parameters mapping multipliers for peak hours pricing.
