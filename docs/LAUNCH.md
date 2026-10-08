# Launching Beast Mode Motors

This takes you from the repo to a live site on your own domain, with a real database, file storage and email.
Plan on 30–45 minutes. Most of it is creating accounts and copying keys between them.

You'll use four services:

| Service | What it does here | Cost (check their pricing pages; these change) |
| --- | --- | --- |
| [Render](https://render.com) | Runs the app (website, email worker, daily jobs) and the Postgres database | About $7/month for the app and about $6/month for the smallest database |
| [Cloudflare R2](https://developers.cloudflare.com/r2/) | Stores car photos and receipts | Free tier covers a small site |
| [Resend](https://resend.com) | Sends email: shop verifications, reminders, deal updates, buyer alerts | Free tier covers a small site |
| Your domain's DNS (at your registrar, or at Cloudflare if you move it there in step 2) | Points your domain at Render and proves to Resend you own it | Whatever your domain costs |

Optional: an [Anthropic API key](https://console.anthropic.com) to read receipts with Claude. Usage is billed per scan
and capped per person per day (`RECEIPT_SCANS_PER_DAY`).

Keep a scratch note open: each step below gives you values that a later step asks for. The note should never be
committed or shared, because it will hold secrets.

---

## 1. Make an app key

The app key encrypts sessions and signs links. Make one and **never change it once the site is live**: changing it
signs everyone out and breaks the signed links already emailed: shop verification requests, shop profile edit links,
alert unsubscribe links and email-confirmation links. Car transfer links are not affected.

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

   A custom domain only works if `yourdomain.com` is a site in this same Cloudflare account. On the Free and Pro
   plans that means using Cloudflare's nameservers. To do that, choose **Add a site** in Cloudflare and check that
   the DNS records it imports include everything you have today, especially the MX and TXT records for your email.
   Then switch the nameservers at your registrar. From then on, add every DNS record in this guide in Cloudflare,
   not at your registrar. If you don't want to move your DNS yet, use the `r2.dev` URL for now and change
   `AWS_PUBLIC_URL` later.
3. **Leave bmm-receipts private.** Never turn on public access for it: no `r2.dev` URL and no custom domain.
4. On the R2 overview page, copy your **Account ID**. Save
   `https://<account-id>.r2.cloudflarestorage.com` as **AWS_ENDPOINT**.
5. **Manage R2 API Tokens → Create API token**: permission *Object Read & Write*, limited to the two buckets.
   Save the **Access Key ID** and **Secret Access Key** as **AWS_ACCESS_KEY_ID** and **AWS_SECRET_ACCESS_KEY**
   (the secret is shown once).

## 3. Email on Resend

1. Sign up, then **Domains → Add domain**. Use your domain or a subdomain such as `mail.yourdomain.com`.
2. Resend shows a few DNS records (for SPF and DKIM). Add them where your domain's DNS is managed (Cloudflare,
   if you moved it in step 2; otherwise your registrar) exactly as shown, then click **Verify**. DNS changes can
   take a few minutes to an hour.
3. **API Keys → Create API key** with *Sending access*. Save it as **MAIL_PASSWORD**.
4. Choose the address mail comes from. The part after `@` must be exactly the domain shown as *Verified* in
   Resend: `hello@yourdomain.com` if you verified `yourdomain.com`, or `hello@mail.yourdomain.com` if you verified
   `mail.yourdomain.com`. Save it as **MAIL_FROM_ADDRESS**. Choose where support mail and ownership-review requests go, e.g.
   `support@yourdomain.com`. Save it as **SUPPORT_EMAIL**. Make sure someone reads that inbox.

## 4. Deploy on Render

> **Already running the free demo from `render.yaml`?** Keep it as its own Blueprint, and never point both
> Blueprints at the same service. The production blueprint uses different names (`beast-mode-motors-prod` and
> `beast-mode-motors-db`), so applying it creates new resources and leaves the demo alone. Don't use the
> "copy over `render.yaml`" fallback in item 3 below while a demo Blueprint still reads `render.yaml`: delete the demo
> Blueprint and its service first.

1. Push this repo to your GitHub account if it isn't there already.
2. In Render, choose **New → Blueprint** and connect the repo.
3. Set the **Blueprint path** to `render.production.yaml`. If your Render dashboard doesn't offer that field,
   copy `render.production.yaml` over `render.yaml` in your repo and use the default.
4. Render lists the database and the web service, and asks for every secret. Fill them in from your note:

   | Setting | Value |
   | --- | --- |
   | `APP_KEY` | from step 1 |
   | `SUPPORT_EMAIL`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS` | from step 3 |
   | `AWS_ENDPOINT`, `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_BUCKET`, `AWS_PUBLIC_BUCKET`, `AWS_PUBLIC_URL` | from step 2 |

   You don't need `APP_URL` yet. Until step 5, the app uses the `….onrender.com` address Render gives the
   service, whatever name it ends up with, and always accepts that address.
5. Click **Apply**. The first build takes a few minutes. In the service's **Logs** you should see the migrations
   run and then the web server start. The service page shows its address: `https://<that address>/up` should
   return a green page.
6. Optional: to read receipts with Claude, open the service's **Environment**, add `ANTHROPIC_API_KEY` with your
   key and save. Without it, the "Fill from receipt" button is hidden.

If Render rejects a plan name in the blueprint (`starter` for the app, `basic-256mb` for the database), change
`plan:` for that resource in `render.production.yaml` to the nearest paid plan Render lists, commit, push and apply
the Blueprint again. Change it in the file, not in the dashboard: the next Blueprint sync would put the old value
back. Don't use the free web plan: it sleeps when idle, which stops the email worker and the daily jobs.

## 5. Your domain

Do these in order. As soon as a service has a custom domain, Render's health checks use that domain, so the app
must accept it **before** you add it. Otherwise Render takes the site offline until it does.

1. In the service's **Environment**, add `TRUSTED_HOSTS` = `yourdomain.com` (this also covers `www.` and any other
   subdomain). Save, and wait until that deploy is live.
2. Open **Settings → Custom Domains** and add `yourdomain.com` (and `www.yourdomain.com` if you want both). Add
   the DNS records Render shows where your DNS is managed. If that's Cloudflare, set those records to **DNS only**
   (grey cloud), or Render can't verify the domain or issue its certificate. Render issues the HTTPS certificate
   itself.
3. Once Render shows the domain as verified with a certificate, add `APP_URL` = `https://yourdomain.com` (or the
   `www.` address, if that's the one you want people to use). Save. Render redeploys, and links in emails now use
   your domain.

The `….onrender.com` address keeps working too.

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
- [ ] Open the receipt from the car's Documents tab: it opens, and its address is on your own site
      (`https://yourdomain.com/garage/…/documents/…`), never an `r2.dev` or photos address. Open that address in a
      private window: it must send you to the sign-in page.
- [ ] In Cloudflare, open **R2 → bmm-receipts → Settings**. Under **Public access**, the `r2.dev` URL must be
      **Disabled** and no custom domain may be connected.
- [ ] Ask the shop on a record to verify it, using an address you can read. The email arrives and the link works.
- [ ] On the Sell tab, add a photo and publish the listing. The photo shows on the listing and its address starts
      with your `AWS_PUBLIC_URL`.
- [ ] As the buyer: save the listing, save a search, send a message and make an offer. The seller sees the message
      in the deal room and under the notification bell (messages aren't emailed), and gets one email, for the
      offer. Saving a listing or a search doesn't email anyone straight away.
- [ ] Open `/admin` and check the listing and both accounts are there.

## After launch

- **Updates:** push to the branch Render deploys from. Each deploy runs any new migrations before the new
  version takes traffic.
- **Backups:** check your Render database's backup settings, and take a manual backup before big changes.
- **Database access:** the database only accepts connections from inside Render. Use the service's **Shell**
  (for example `php artisan tinker`). To run `psql` from your own machine, add your IP to `ipAllowList` in
  `render.production.yaml` for a short time, rather than in the dashboard, where the next Blueprint sync would
  undo it.
- **Daily jobs** run inside the app: maintenance and expiry reminders at 8:00, buyer alerts at 7:30, recall
  checks on Mondays, housekeeping hourly (times in `APP_TIMEZONE`). Their output and any errors are in the Render
  logs. To rerun one by hand, use the **Shell**, for example `php artisan passport:sync-recalls`.
- **Email problems** usually come from a domain that isn't verified in Resend, or a `MAIL_FROM_ADDRESS` that isn't
  on exactly that domain. Failed sends show in the logs, and failed jobs are kept for a week.

## Troubleshooting

| Symptom | Fix |
| --- | --- |
| A deploy fails its health checks | Open that deploy's **Logs**: a migration error or a missing setting is named there. If it started after you added a custom domain, the domain must be `APP_URL`'s host or be listed in `TRUSTED_HOSTS` (step 5) |
| Your domain shows "400 Bad Request" | The domain must be `APP_URL`'s host (or a subdomain of it) or be listed in `TRUSTED_HOSTS` |
| Links in emails point to the `onrender.com` address or the wrong domain | Set `APP_URL` to the `https://` address people use, and let Render redeploy |
| "Page expired" on every form, or sign-in doesn't stick | Check that `APP_KEY` hasn't changed and that the deploy's migrations ran (the `sessions` table must exist). An occasional "Page expired" after a page sits open for more than 2 hours is normal |
| Uploading a photo or receipt shows an error | The Render logs show `Unable to write file` with the storage error. `NoSuchBucket`: check the spelling of `AWS_BUCKET` and `AWS_PUBLIC_BUCKET`. `AccessDenied` or `InvalidAccessKeyId`: check the keys, and that the R2 token covers **both** buckets. A connection error: check `AWS_ENDPOINT`. An ACL error (`AccessControlListNotSupported` or `NotImplemented`): set `AWS_PUBLIC_ACL=false` (already set in the blueprint) |
| Photos upload but show as broken images | `AWS_PUBLIC_URL` must be the photos bucket's public URL, and public access must be on for that bucket |
| Everyone signed out after a settings change | `APP_KEY` changed. Put the original back |
| Emails stuck | Check the logs for SMTP errors. The domain must show *Verified* in Resend, and `MAIL_FROM_ADDRESS` must be on exactly that domain |
