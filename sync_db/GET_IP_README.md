# get_ip.php - IP Detection API

## Purpose

This simple API endpoint returns the client's IP address as seen by the server. It's used by the "Check My IP" feature to show you exactly which IP address your remote server sees when you connect.

## Security

**No authentication is required** - and that's intentional! This file:
- Only returns the requesting IP address
- Contains no sensitive information
- Doesn't access any databases
- Can be safely called by anyone

The worst someone could do is check their own IP address.

## Deployment

Upload this file to your **REMOTE server** at:
```
/path/to/db-manager/sync_db/get_ip.php
```

It should be in the same directory as `api.php`.

## Usage

### From Browser
```
https://your-server.com/db-manager/sync_db/get_ip.php
```

### Response Format
```json
{
  "success": true,
  "ip": "81.204.237.36",
  "timestamp": "2025-10-21 14:30:15",
  "server": "www.wijs.ovh"
}
```

### From Check IP Tool

The `check_ip.php` page automatically calls this endpoint to show your public IP. It:
1. Reads your saved remote server URL from cookies
2. Replaces `api.php` with `get_ip.php` in the path
3. Makes a request to get your public IP
4. Displays the IP prominently

## How It Works

1. Client connects from their local server (localhost)
2. Request goes through the internet to your remote server
3. Remote server sees the client's **public IP** (not localhost)
4. `get_ip.php` returns that public IP
5. Client now knows which IP to whitelist

## Example Flow

```
Your Computer (localhost)
    ↓
Internet (your public IP: 81.204.237.36)
    ↓
Remote Server (wijs.ovh)
    ↓
get_ip.php returns: "81.204.237.36"
    ↓
You see: "Add 81.204.237.36 to ipAllowed.txt"
```

## Benefits Over External Services

Using your own remote server instead of public IP services like ipify.org:
- ✅ Shows the **exact IP** your remote server sees
- ✅ No dependency on external services
- ✅ More reliable and faster
- ✅ Works even if external services are down
- ✅ Same IP detection logic as the main API

## Troubleshooting

### "Could not connect to remote server"
- Check that `get_ip.php` is uploaded to the remote server
- Verify the remote server URL is correct
- Make sure the file is in the same directory as `api.php`

### Shows local IP (::1 or 127.0.0.1)
- Fill in the "Remote Server URL" field on the sync page first
- The URL is saved in a cookie
- Then visit the "Check My IP" page again

### Wrong IP displayed
- The IP shown is the one your remote server sees
- If behind a proxy/NAT, this might be the proxy's IP
- This is correct - add this IP to the whitelist

## Technical Details

### Headers Checked (in order)
1. `HTTP_CLIENT_IP`
2. `HTTP_X_FORWARDED_FOR` (first IP if multiple)
3. `HTTP_X_FORWARDED`
4. `HTTP_FORWARDED_FOR`
5. `HTTP_FORWARDED`
6. `REMOTE_ADDR` (fallback)

### CORS Headers
The endpoint includes CORS headers to allow cross-origin requests:
- `Access-Control-Allow-Origin: *`
- `Access-Control-Allow-Methods: GET, POST, OPTIONS`
- `Access-Control-Allow-Headers: Content-Type`

This allows your local server to call the remote server's API.

## File Size
Approximately 2KB - very lightweight!

## No Maintenance Required
Once uploaded, this file requires no configuration or updates.

