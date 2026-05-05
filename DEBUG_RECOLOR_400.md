# 🔍 Debug Guide: Recolor 400 Bad Request

## Problem

Backend returning `HTTP 400` for `/api/recolor` endpoint.

```
POST /api/recolor HTTP/1.1" 400 -
```

This means:

- ✅ Endpoint EXISTS
- ❌ Request format is WRONG
- ❌ Backend cannot parse the request

---

## What 400 Error Means

HTTP 400 Bad Request = Backend received request but payload is invalid

**Common causes:**

1. Wrong field names (e.g., `palet` instead of `palette`)
2. Wrong data format (e.g., array instead of JSON string)
3. Missing required fields
4. Corrupted image data
5. Invalid JSON in palette

---

## Current Request Format

### What Laravel Sends

```php
Http::timeout(120)
    ->attach('image', $imageContent, 'batik.jpg')
    ->attach('palette', $paletteJson)              // JSON string
    ->attach('white_threshold', '150')
    ->post('http://localhost:5000/api/recolor');
```

### HTTP Representation

```
POST /api/recolor HTTP/1.1
Host: localhost:5000
Content-Type: multipart/form-data; boundary=----WebKitFormBoundary

------WebKitFormBoundary
Content-Disposition: form-data; name="image"; filename="batik.jpg"
Content-Type: image/jpeg

[binary image data...]
------WebKitFormBoundary
Content-Disposition: form-data; name="palette"

["#FF0000","#00FF00","#0000FF"]
------WebKitFormBoundary
Content-Disposition: form-data; name="white_threshold"

150
------WebKitFormBoundary--
```

---

## Debugging Steps

### Step 1: Check Backend Error Message

Look at backend logs when request is made:

```
POST /api/recolor HTTP/1.1" 400 -
```

Try to find MORE details in backend console output. It should show:

```
Error: Invalid request - [detailed error message]
```

### Step 2: Verify Palette Format

Current format: `["#FF0000","#00FF00","#0000FF"]`

**Check:**

- All colors start with `#`
- All colors are uppercase
- All colors are valid HEX (6 characters)
- It's valid JSON

Test with curl:

```bash
# Create test palette
PALETTE='["#FF0000","#00FF00","#0000FF"]'

# Validate it's valid JSON
echo $PALETTE | jq .
# Should output without error

# Test endpoint
curl -X POST http://localhost:5000/api/recolor \
  -F "image=@test_image.jpg" \
  -F "palette=$PALETTE" \
  -F "white_threshold=150" \
  -v
```

### Step 3: Check Image Format

Current: Binary JPEG image as `image` field

**Test:**

```bash
# Verify image is valid JPEG
file test_image.jpg
# Should show: JPEG image data, JFIF standard

# Test with curl
curl -X POST http://localhost:5000/api/recolor \
  -F "image=@test_image.jpg" \
  -F "palette=[\"#FF0000\"]" \
  -v
```

### Step 4: Check Backend Expectations

Backend might expect different format. Check backend code or error logs.

**Possibilities:**

| Format                         | Example                   | Status             |
| ------------------------------ | ------------------------- | ------------------ |
| palette as JSON string         | `'["#FF0000"]'`           | Currently trying ✓ |
| palette as form field array    | `palette[]=1&palette[]=2` | Maybe?             |
| palette as different structure | `{colors: [...]}`         | Maybe?             |
| color format                   | `"#FF0000"` vs `"FF0000"` | Unknown            |

---

## Possible Fixes

### Option 1: Backend Expected Different Field Names

Check if backend wants different names:

- `colors` instead of `palette`
- `pallet` (typo?) instead of `palette`
- `recolor_palette` instead of `palette`

### Option 2: Backend Expected Array Format

Instead of JSON string, maybe backend wants URL-encoded array:

```php
Http::attach('image', $imageContent, 'batik.jpg')
    ->attach('palette[]', $color1)
    ->attach('palette[]', $color2)
    ->attach('palette[]', $color3)
    ->attach('white_threshold', '150')
    ->post($url);
```

### Option 3: Backend Expected Different Color Format

Maybe without `#` prefix:

```php
$paletteHex = array_map(function($color) {
    return str_replace('#', '', $color);
}, $paletteHex);
```

### Option 4: Check Backend Response Body

Update `attemptRecolor()` to log response body even on 400:

```php
$responseBody = $recolorResponse->body();
Log::error('Backend 400 error', [
    'status' => 400,
    'body' => $responseBody,
    'json' => json_decode($responseBody, true),
]);
```

---

## What to Check Next

### 1. **Check Backend Code**

Look at your Flask backend's `/api/recolor` handler:

```python
@app.route('/api/recolor', methods=['POST'])
def recolor():
    # What fields does it expect?
    # palette = request.form.get('palette')  # ?
    # palette = request.form.getlist('palette')  # ?
    # What format?
```

### 2. **Check Backend Error Response**

When you get 400, what does response body say?

```bash
# Run verify-backend.php to see response body
php verify-backend.php
```

### 3. **Test with Postman/cURL**

Use exact same format as Laravel to see if it works:

```bash
curl -X POST http://localhost:5000/api/recolor \
  -F "image=@/path/to/image.jpg" \
  -F 'palette=["#FF0000","#00FF00"]' \
  -F "white_threshold=150" \
  -v
```

If curl works but Laravel doesn't → encoding issue
If curl fails too → backend format issue

---

## Logs to Check

### Laravel Log

```bash
# Watch in real-time
tail -f storage/logs/laravel.log | grep -i "recolor\|backend"

# Look for:
- "Attempting recolor"
- "Recolor response received"
- "Recolor API returned error"
- "Backend error: ..."
```

### Backend Log

Your Flask backend should have logs showing:

```
POST /api/recolor
REQUEST DATA: {'image': <binary>, 'palette': ..., 'white_threshold': ...}
ERROR: [specific validation error message]
```

---

## Action Plan

1. **Check backend code** - What fields/format does it expect?
2. **Check backend logs** - What error message for 400?
3. **Test with curl** - Does manual request work?
4. **Update Laravel request** - Match format that works
5. **Test workflow again** - Should pass now

---

## Reference: What We Know

✅ Working:

- `/api/palette/extract` returns 200 ✓
- Backend is accessible
- Image encoding works

❌ Not Working:

- `/api/recolor` returns 400
- Endpoint exists but validation fails
- Either field names or format wrong

---

**Next Step:** Check backend error response body or backend code to understand what format it expects.
