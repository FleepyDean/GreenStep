# Cloudinary Setup for Photo Uploads

## Problem
Railway uses **ephemeral filesystem** - any files uploaded to the server are deleted on redeploy. This is why photos don't persist in production.

## Solution
Use **Cloudinary** (free cloud image storage) to store activity photos permanently.

## Setup Steps

### 1. Create Cloudinary Account
1. Go to https://cloudinary.com
2. Click "Sign Up" (free tier: 25GB storage, 25GB bandwidth/month)
3. Verify your email

### 2. Get Your Credentials
1. Log in to Cloudinary dashboard
2. Go to **Dashboard** (home page)
3. Find your credentials in the "Account Details" section:
   - **Cloud Name**: `dxxxxxxxx`
   - **API Key**: `123456789012345`
   - **API Secret**: `abcdefghijklmnopqrstuvwxyz123456`

### 3. Add to Railway Environment Variables
1. Go to your Railway project
2. Click on your **Backend service**
3. Go to **Variables** tab
4. Add these three variables:
   ```
   CLOUDINARY_CLOUD_NAME=your-cloud-name-here
   CLOUDINARY_API_KEY=your-api-key-here
   CLOUDINARY_API_SECRET=your-api-secret-here
   ```
5. Click **Deploy** to restart with new variables

### 4. Deploy Updated Code
```bash
git add .
git commit -m "Add Cloudinary photo upload support"
git push
```

Railway will auto-deploy the changes.

### 5. Test Photo Upload
1. Go to https://greenstep.up.railway.app
2. Log in
3. Navigate to Activity Log
4. Upload a photo with an activity
5. Check Railway database - `photo_url` should now contain a Cloudinary URL like:
   ```
   https://res.cloudinary.com/dxxxxxxxx/image/upload/v1234567890/greenstep/activities/abc123.jpg
   ```

## How It Works

**Before (Local Filesystem - Doesn't work on Railway):**
```
Photo → Railway Server → /public/uploads/activities/photo.jpg
                         ❌ Deleted on redeploy
```

**After (Cloudinary - Permanent Storage):**
```
Photo → Railway Server (temp) → Cloudinary Cloud → Permanent URL
                                                   ✅ Never deleted
```

## Code Changes Made

1. **Created** `api/src/Services/CloudinaryUploader.php` - Handles upload to Cloudinary
2. **Updated** `api/src/Controllers/ActivityController.php` - Uses Cloudinary instead of local filesystem
3. **Updated** `api/.env.production` - Added Cloudinary credentials template

## Cloudinary Features

- **Free Tier**: 25GB storage, 25GB bandwidth/month
- **Automatic Optimization**: Images are automatically optimized for web
- **CDN**: Fast global delivery
- **Transformations**: Can resize/crop images on-the-fly
- **Permanent Storage**: Files never deleted unless you manually delete them

## Folder Structure in Cloudinary

All activity photos are stored in:
```
greenstep/
  └── activities/
      ├── photo1.jpg
      ├── photo2.png
      └── photo3.webp
```

## Troubleshooting

**Photos still not uploading?**
1. Check Railway logs for errors: `Failed to upload photo to Cloudinary`
2. Verify Cloudinary credentials are correct
3. Check Cloudinary dashboard → Media Library to see if uploads are appearing

**Cloudinary quota exceeded?**
- Free tier: 25GB/month bandwidth
- Upgrade to paid plan or optimize image sizes

## Alternative Solutions

If you don't want to use Cloudinary:

1. **AWS S3** - More complex setup, pay-as-you-go
2. **Imgur API** - Free but limited features
3. **Base64 in Database** - Not recommended (large database size)
4. **Disable Photo Uploads** - Remove feature entirely
