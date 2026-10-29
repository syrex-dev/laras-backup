# TODO - Surat Keluar Live Preview Fix

- [x] Update `resources/views/surat-keluar/create.blade.php`
  - [x] Add Template Surat dropdown (name=`templat_surat_id`) using `$templates`
  - [x] Extend Alpine `formData` to include `templat_surat_id`
  - [x] Frontend validate: if template not selected -> abort/alert preview fetch

- [x] Update `app/Http/Controllers/Letters/SuratKeluarController.php` -> `preview()`
  - [x] Accept incoming `templat_surat_id` from AJAX payload
  - [x] Ensure mock `$surat` includes `templat_surat_id`
  - [x] Ensure `$qrCode` is always defined for the Blade fallback render
  - [x] Harden `penyetuju` access in `$replacements` using `nama` or `name` safely
  - [x] Improve backend error handling: return an iframe-friendly HTML error page on failure (no raw JSON)

- [x] Automated & Manual Tests
  - [x] 11 unit & feature tests passing (48 assertions)
  - [x] Secretary edit form includes template selection for surat keluar
  - [x] Full integration create then approve

