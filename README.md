# Nimbbl Payment Gateway — OpenCart Plugin

Nimbbl payment gateway plugin for **OpenCart 3** and **OpenCart 4**, enabling seamless checkout via the Nimbbl Sonic SDK.

---

## Supported Versions

| Platform   | Plugin version | Zip name                        |
|------------|----------------|---------------------------------|
| OpenCart 2 | v1.x           | `Opencart2_Nimbbl.ocmod.zip`   |
| OpenCart 3 | v2.x – v3.x    | `Opencart3[.x.x]_Nimbbl.ocmod.zip` |
| OpenCart 4 | v4.x           | `nimbbl.ocmod.zip`             |

---

## Release Zips

Pre-built plugin zips are in the `public/` directory, versioned by plugin release:

```
public/
  opencart2/
    v1.0.0/  Opencart2_Nimbbl.ocmod.zip
  opencart3/
    v2.0.0/  Opencart3_Nimbbl.ocmod.zip
    v3.0.1/  Opencart3.0.1_Nimbbl.ocmod.zip
    v3.0.3/  Opencart3.0.3_Nimbbl.ocmod.zip
    v3.0.4/  Opencart3.0.4_Nimbbl.ocmod.zip
  opencart4/
    v4.0.0/  nimbbl.ocmod.zip
```

> **Why `nimbbl.ocmod.zip`?** OC4's Extension Installer derives the extension code
> from the zip filename (`basename(.ocmod.zip)`). The code must be `nimbbl` for the
> plugin's routing and namespace resolution to work. Pre-release zips append the
> suffix so the stable slot is always `nimbbl.ocmod.zip`:

```
v4.1.0-alpha.1/  nimbbl-alpha.1.ocmod.zip
v4.1.0-beta.2/   nimbbl-beta.2.ocmod.zip
v4.1.0-rc.1/     nimbbl-rc.1.ocmod.zip
```

---

## Installation (OpenCart 4)

1. Download `Opencart4_Nimbbl.ocmod.zip` from [GitHub Releases](../../releases) or `public/opencart4/`.
2. OpenCart Admin → **Extensions → Installer** → Upload the zip.
3. **Extensions → Payments → Nimbbl** → click **Install**.
4. Click **Edit**, enter your **Test/Live API Keys** and **Save**.
5. The webhook URL is shown in the settings — add it to your Nimbbl dashboard.

### Requirements

- OpenCart 4.x
- PHP 8.1+
- HTTPS on the store (required by Nimbbl Sonic checkout)

---

## Installation (OpenCart 3)

1. Download the appropriate `Opencart3_Nimbbl.ocmod.zip` from `public/opencart3/`.
2. OpenCart Admin → **Extensions → Installer** → Upload the zip.
3. **Extensions → Modifications** → click **Refresh**.
4. **Extensions → Payments → Nimbbl** → Install → Edit → configure API keys.

---

## Building the OpenCart 4 Zip

The OC4 plugin source lives in `src/opencart4/`. Use `src/build.sh` to build the distributable zip:

```bash
# Stable release
bash src/build.sh 4.0.0
# → public/opencart4/v4.0.0/Opencart4_Nimbbl.ocmod.zip

# Alpha / Beta / RC
bash src/build.sh 4.1.0-alpha.1
# → public/opencart4/v4.1.0-alpha.1/Opencart4_Nimbbl-alpha.1.ocmod.zip
```

### Requirements

- `bash`, `zip`, `rsync`
- `composer` (only if `nimbbl-sdk` is not already bundled)

---

## Releasing (OpenCart 4)

Push a version tag to trigger the GitHub Actions release workflow, which builds the zip and creates a GitHub Release automatically:

```bash
# Stable
git tag v4.0.0 && git push origin v4.0.0

# Alpha / Beta / RC
git tag v4.1.0-alpha.1 && git push origin v4.1.0-alpha.1
git tag v4.1.0-beta.2  && git push origin v4.1.0-beta.2
git tag v4.1.0-rc.1    && git push origin v4.1.0-rc.1
```

The workflow (`.github/workflows/release.yml`) will:
1. Run `src/build.sh {VERSION}` to produce the zip in `public/opencart4/v{VERSION}/`
2. Create a GitHub Release with the zip attached

---

## Project Structure

```
src/
  build.sh                         ← Build script for OC4 zip
  opencart4/
    install.json                   ← OC4 Extension Installer metadata
    upload/
      extension/nimbbl/
        catalog/controller/payment/nimbbl.php   ← Checkout flow
        catalog/model/payment/nimbbl.php
        catalog/view/template/payment/
          nimbbl_redirect.twig     ← Nimbbl Sonic SDK integration
          nimbbl.twig
        admin/controller/payment/nimbbl.php     ← Admin settings
        admin/view/template/payment/nimbbl.twig
        system/library/
          nimbbl-sdk/              ← Nimbbl PHP SDK
          vendor/                  ← rmccue/requests (HTTP library)

public/
  opencart2/                       ← OC2 release zips
  opencart3/                       ← OC3 release zips
  opencart4/                       ← OC4 release zips (built by build.sh)
```

---

## Support

For integration help, contact [help@nimbbl.biz](mailto:help@nimbbl.biz) or visit [nimbbl.biz](https://nimbbl.biz).
