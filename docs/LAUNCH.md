# Launching Beast Mode Motors

This takes you from the repo to a live site on your own domain, with a real database, file storage and email.
Plan on 30–45 minutes. Most of it is creating accounts and copying keys between them.

You'll use four services:

| Service | What it does here | Cost (check their pricing pages; these change) |
| --- | --- | --- |
| [Render](https://render.com) | Runs the app (website, email worker, daily jobs) and the Postgres database | About $7/month for the app and about $6/month for the smallest database |
| [Cloudflare R2](https://developers.cloudflare.com/r2/) | Stores car photos and receipts | Free tier covers a small site |
| [Resend](https://resend.com) | Sends email: shop verifications, reminders, deal updates, buyer alerts | Free tier covers a small site |
| Your domain registrar | Points your domain at Render and proves to Resend you own it | Whatever your domain costs |

Optional: an [Anthropic API key](https://console.anthropic.com) to read receipts with Claude. Usage is billed per scan
and capped per person per day (`RECEIPT_SCANS_PER_DAY`).

Keep a scratch note open: each step below gives you values that a later step asks for. The note should never be
committed or shared, because it will hold secrets.

---

## 1. Make an app key

The app key encrypts sessions and signed links. Make one and **never change it once the site is live**: changing it
signs everyone out and breaks unsubscribe and transfer links already sent.

If you have PHP:

```bash
php artisan key:generate --show
```

Or with Docker:

```bash
docker run --rm php:8.3-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
```

Save the whole `base64:…` value as **APP_KEY**.

## 2. File storage on Cloudflare R2

The app stores two kinds of file, and they need different buckets:

- **Receipts and paperwork** (insurance, registration, inspection reports). These are private: the app streams
  them only to people allowed to see them.
- **Car photos.** These are public, so listings load fast.

R2 makes a bucket either entirely public or entirely private, so use one of each.

1. In the Cloudflare dashboard, open **R2 Object Storage** and create two buckets, for example
   `bmm-receipts` and `bmm-photos`. Save them as **AWS_BUCKET** (receipts) and **AWS_PUBLIC_BUCKET** (photos).
2. Open **bmm-photos → Settings → Public access**. Connect a custom domain such as `photos.yourdomain.com`
   (recommended), or enable the `r2.dev` subdomain to get started. The `r2.dev` URL is rate-limited and
   meant for testing. Save that URL, without a trailing slash, as **AWS_PUBLIC_URL**.
3. **Leave bmm-receipts private.** Never turn on public access for it.
4. On the R2 overview page, copy your **Account ID**. Save
   `https://<account-id>.r2.cloudflarestorage.com` as **AWS_ENDPOINT**.
5. **Manage R2 API Tokens → Create API token**: permission *Object Read & Write*, limited to the two buckets.
   Save the **Access Key ID** and **Secret Access Key** as **AWS_ACCESS_KEY_ID** and **AWS_SECRET_ACCESS_KEY**
   (the secret is shown once).

## 3. Email on Resend

1. Sign up, then **Domains → Add domain**. Use your domain or a subdomain such as `mail.yourdomain.com`.
2. Resend shows a few DNS records (for SPF and DKIM). Add them at your registrar exactly as shown, then click
   **Verify**. DNS changes can take a few minutes to an hour.
3. **API Keys → Create API key** with *Sending access*. Save it as **MAIL_PASSWORD**.
4. Choose the address mail comes from, on the verified domain, e.g. `hello@yourdomain.com`. Save it as
   **MAIL_FROM_ADDRESS**. Choose where support mail and ownership-review requests go, e.g.
   `support@yourdomain.com`. Save it as **SUPPORT_EMAIL**. Make sure someone reads that inbox.

## 4. Deploy on Render

1. Push this repo to your GitHub account if it isn't there already.
2. In Render, choose **New → Blueprint** and connect the repo.
3. Set the **Blueprint path** to `render.production.yaml`. If your Render dashboard doesn't offer that field,
   copy `render.production.yaml` over `render.yaml` in your repo and use the default.
4. Render lists the database and the web service, and asks for every secret. Fill them in from your note:

   | Setting | Value |
   | --- | --- |
   | `APP_KEY` | from step 1 |
   | `APP_URL` | `https://beast-mode-motors.onrender.com` for now (step 5 switches it to your domain) |
   | `TRUSTED_HOSTS` | `beast-mode-motors.onrender.com` |
   | `SUPPORT_EMAIL`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` | from step 3 |
   | `AWS_ENDPOINT`, `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET`, `AWS_PUBLIC_BUCKET`, `AWS_PUBLIC_URL` | from step 2 |
   | `ANTHROPIC_API_KEY` | optional: leave empty to hide receipt scanning |

   If Render named the service differently, use its actual `….onrender.com` address in both places.
5. Click **Apply**. The first build takes a few minutes. In the service's **Logs** you should see the migrations
   run and then the web server start. `https://<service>.onrender.com/up` should return a green page.

If Render rejects a plan name in the blueprint (`starter` for the app, `basic-256mb` for the database), choose the
nearest paid plan in the dashboard. Don't use the free web plan: it sleeps when idle, which stops the email
worker and the daily jobs.

## 5. Your domain

1. In the Render service, open **Settings → Custom Domains** and add `yourdomain.com` (and `www.yourdomain.com` if
   you want both). Add the DNS records Render shows at your registrar. Render issues the HTTPS certificate itself.
2. Once Render shows the domain as verified, update the environment:
   - `APP_URL` = `https://yourdomain.com`
   - `TRUSTED_HOSTS` = `beast-mode-motors.onrender.com,www.yourdomain.com`. Keep the onrender.com name so
     Render's health checks keep passing.
3. Save. Render redeploys. Links in emails now use your domain.

## 6. Your admin account

1. Sign up on the site with your own email, like any user.
2. In the Render service, open **Shell** and run:

   ```bash
   php artisan passport:make-admin you@yourdomain.com
   ```

3. Sign in and open `/admin`. That's the trust & safety panel: reports, listings, accounts, shops and disputes.
   To remove access later, run the same command with `--revoke`.

## 7. Check it end to end

Do this once, with a second email address playing the buyer.

- [ ] Sign up and sign in. Request a password reset and check the email arrives from your address.
- [ ] Add a car by VIN, and log a service record with a receipt attached.
- [ ] Open the receipt from the car's Documents tab: it opens. Copy the file's address from R2 and open it in a
      private window: it must **not** load, because the receipts bucket is private.
- [ ] Ask the shop on a record to verify it, using an address you can read. The email arrives and the link works.
- [ ] On the Sell tab, add a photo and publish the listing. The photo shows on the listing and its address starts
      with your `AWS_PUBLIC_URL`.
- [ ] As the buyer: save the listing, save a search, send a message and make an offer. The seller gets the emails.
- [ ] Open `/admin` and check the listing and both accounts are there.

## After launch

- **Updates:** push to the branch Render deploys from. Each deploy runs any new migrations before the new
  version takes traffic.
- **Backups:** check your Render database's backup settings, and take a manual backup before big changes.
- **Daily jobs** run inside the app: maintenance and expiry reminders at 8:00, buyer alerts at 7:30, recall
  checks on Mondays, housekeeping hourly (times in `APP_TIMEZONE`). Their output is in the Render logs.
- **Email problems** usually come from a domain that isn't verified in Resend, or a `MAIL_FROM_ADDRESS` on a
  different domain. Failed sends show in the logs, and failed jobs are kept for a week.

## Troubleshooting

| Symptom | Fix |
| --- | --- |
| Deploy succeeds but the health check fails | `TRUSTED_HOSTS` must include the service's `onrender.com` name |
| "Page expired", or sign-in doesn't stick | `APP_URL` must be the `https://` address people actually use |
| Photos upload but show as broken images | `AWS_PUBLIC_URL` must be the photos bucket's public URL, with public access on and no trailing slash |
| Uploading fails with `AccessControlListNotSupported` | Set `AWS_PUBLIC_ACL=false` (already set in the blueprint) |
| Everyone signed out after a settings change | `APP_KEY` changed. Put the original back |
| Emails stuck | Check the logs for SMTP errors, and that the domain shows *Verified* in Resend |
