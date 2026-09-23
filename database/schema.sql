-- ShareHub SQLite schema

CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  email TEXT UNIQUE NOT NULL,
  password_hash TEXT NOT NULL,
  role TEXT NOT NULL DEFAULT 'student', -- 'student' | 'admin'
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS subscriptions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  category TEXT NOT NULL,
  icon TEXT,
  base_price REAL NOT NULL,
  description TEXT
);

CREATE TABLE IF NOT EXISTS groups (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  subscription_id INTEGER NOT NULL REFERENCES subscriptions(id),
  created_by INTEGER NOT NULL REFERENCES users(id),
  max_members INTEGER NOT NULL,
  cost_per_slot REAL NOT NULL,
  status TEXT NOT NULL DEFAULT 'recruiting', -- 'recruiting' | 'active' | 'full'
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS group_members (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  group_id INTEGER NOT NULL REFERENCES groups(id),
  user_id INTEGER NOT NULL REFERENCES users(id),
  payment_status TEXT NOT NULL DEFAULT 'unpaid', -- 'unpaid' | 'paid'
  joined_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS transactions (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  user_id INTEGER NOT NULL REFERENCES users(id),
  group_id INTEGER NOT NULL REFERENCES groups(id),
  amount REAL NOT NULL,
  platform_fee REAL NOT NULL DEFAULT 10,
  status TEXT NOT NULL DEFAULT 'paid',
  paid_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
