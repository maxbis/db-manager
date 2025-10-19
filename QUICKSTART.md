# 🚀 Quick Start - Saved Queries (LocalStorage)

## ✅ Ready to Use!

No setup needed! Just start using saved queries immediately.

---

## 📖 5-Minute Tutorial

### 1️⃣ Save Your First Query (30 seconds)

1. Open `query.php` in your browser
2. Select a table from dropdown (optional)
3. Type a query:
   ```sql
   SELECT * FROM users LIMIT 10
   ```
4. Click **💾 Save Query** button
5. Enter name: "Get 10 Users"
6. Click **💾 Save**

✅ Done! Query saved instantly!

---

### 2️⃣ Load a Saved Query (10 seconds)

1. Look at the right panel **"💾 Saved Queries"**
2. Find your query: "Get 10 Users"
3. Click **📂 Load** button

✅ Query appears in editor!

---

### 3️⃣ Execute Query (5 seconds)

1. Click **▶ Execute Query**
2. Results appear below

✅ Data displayed!

---

### 4️⃣ Export Backup (15 seconds)

1. Click **⬇️** button (top right of saved queries)
2. File downloads: `saved-queries-2025-01-17.json`
3. Save it somewhere safe

✅ Backup created!

---

### 5️⃣ Delete Query (10 seconds)

1. Find a query you don't need
2. Click **🗑️** button
3. Confirm deletion

✅ Query removed!

---

## 🎯 Common Use Cases

### Daily Reports
```sql
-- Save as: "Daily Active Users"
SELECT COUNT(*) as active_users 
FROM users 
WHERE last_login >= CURDATE()
```

### Data Analysis
```sql
-- Save as: "Top 10 Customers by Revenue"
SELECT customer_id, SUM(order_total) as revenue
FROM orders
GROUP BY customer_id
ORDER BY revenue DESC
LIMIT 10
```

### Maintenance
```sql
-- Save as: "Find Orphaned Records"
SELECT * FROM orders 
WHERE customer_id NOT IN (SELECT id FROM customers)
```

---

## 💡 Pro Tips

### Tip 1: Click Field Names
Click any field name in the left panel to insert it into your query!

### Tip 2: Export Weekly
Set a reminder to export queries every week for backup.

### Tip 3: Use Descriptions
Add descriptions to remember what each query does:
```
Name: Monthly Revenue Report
Description: Sums all orders from last 30 days, grouped by product
```

### Tip 4: Table Association
Save queries for specific tables to keep them organized.

### Tip 5: Watch Usage Stats
The "Used: 5x" counter shows your most-used queries.

---

## 🔍 Where Is My Data?

### View in Browser:
1. Press **F12** (Developer Tools)
2. Go to **Application** tab (Chrome) or **Storage** tab (Firefox)
3. Click **Local Storage** → Your domain
4. Look for key: `savedQueries`

You'll see your queries stored as JSON!

---

## ⚡ Performance

Everything is **instant**:
- Save: <1ms
- Load: <1ms
- Delete: <1ms
- List: <1ms

No loading spinners, no delays! 🚀

---

## 📦 Capacity

You can store:
- **~25,000 queries** (5MB limit)
- **~50,000 queries** (10MB limit)

Realistically, you'll use ~10-100 queries = **~5-50 KB**

---

## 🛡️ Data Safety

### ✅ Your queries are safe when:
- Closing browser
- Restarting computer
- Browser updates
- Opening new tabs

### ⚠️ Export regularly because:
- User might clear browser data
- Switching browsers/computers
- Reinstalling browser
- Using Incognito mode (not saved)

**Solution:** Click ⬇️ Export and keep the JSON file safe!

---

## 🔄 Import/Export

### Export Format:
```json
{
  "version": "1.0",
  "exported_at": "2025-01-17T10:30:00.000Z",
  "query_count": 3,
  "queries": [
    {
      "id": 1737120456789,
      "query_name": "Get All Users",
      "query_sql": "SELECT * FROM users LIMIT 100",
      "table_name": "users",
      "description": "Fetch all users",
      "created_at": "2025-01-17T10:30:00.000Z",
      "last_used_at": "2025-01-17T15:45:00.000Z",
      "use_count": 5
    }
  ]
}
```

### Share Queries:
1. Export your queries
2. Send JSON file to colleague
3. They click Import
4. Queries merge automatically!

---

## 🐛 Troubleshooting

### Problem: "No saved queries yet"
**Solution:** This is normal! Save your first query.

### Problem: Queries disappeared
**Solution:** 
1. Check if you cleared browser data
2. Import your backup JSON file
3. Start exporting regularly

### Problem: Can't save
**Solution:**
1. Check browser console (F12)
2. Verify LocalStorage is enabled
3. Not in Incognito mode

### Problem: Export doesn't work
**Solution:**
1. Check pop-up blocker
2. Verify download permissions
3. Try different browser

---

## 📚 More Information

For detailed documentation:
- **LOCALSTORAGE_README.md** - Complete guide
- **MIGRATION_NOTICE.md** - What changed
- Browser DevTools - View your data

---

## 🎉 That's It!

You're ready to use saved queries!

**Remember:**
1. Save useful queries
2. Export weekly for backup
3. Enjoy the speed! 🚀

**Happy querying!** 😊

---

*Questions? Check the browser console (F12) for helpful error messages.*

