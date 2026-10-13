<?php
/**
 * Edit profile view — about, photos, settings tabs.
 */
$title = 'Edit profile';
require __DIR__ . '/_layout_top.php';
?>

<div class="container">
  <div style="padding-top:2.4rem;padding-bottom:3rem">
    <div class="section-head" style="margin-bottom:1.6rem">
      <span class="eyebrow">Settings</span>
      <h2 style="font-size:1.9rem">Edit profile</h2>
    </div>

    <div class="tabs" role="tablist">
      <a class="tab <?= $tab==='about'?'active':'' ?>" href="/edit_profile.php?tab=about" role="tab">About</a>
      <a class="tab <?= $tab==='photos'?'active':'' ?>" href="/edit_profile.php?tab=photos" role="tab">Photos <span class="badge-count"><?= count($myPhotos) ?></span></a>
      <a class="tab <?= $tab==='settings'?'active':'' ?>" href="/edit_profile.php?tab=settings" role="tab">Settings</a>
    </div>

    <!-- ══ ABOUT ══ -->
    <?php if ($tab === 'about'): ?>
      <form class="card card-pad" method="post" action="/edit_profile.php" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">

        <div class="row-2">
          <div class="field">
            <label for="first_name">First name</label>
            <input class="input<?= field_has_error($errors,'first_name') ?>" type="text" id="first_name" name="first_name" value="<?= e($me['first_name'] ?? '') ?>" required maxlength="60">
            <?php if ($errors['first_name']): ?><div class="err">⚠ <?= e($errors['first_name']) ?></div><?php endif; ?>
          </div>
          <div class="field">
            <label for="last_name">Last name</label>
            <input class="input" type="text" id="last_name" name="last_name" value="<?= e($me['last_name'] ?? '') ?>" maxlength="60">
          </div>
        </div>

        <div class="row-2">
          <div class="field">
            <label for="username">Username</label>
            <input class="input<?= field_has_error($errors,'username') ?>" type="text" id="username" name="username" value="<?= e($me['username'] ?? '') ?>" required minlength="3" maxlength="30" pattern="[A-Za-z0-9_]+">
            <?php if ($errors['username']): ?><div class="err">⚠ <?= e($errors['username']) ?></div><?php endif; ?>
          </div>
          <div class="field">
            <label for="birth_date">Birth date</label>
            <input class="input<?= field_has_error($errors,'birth_date') ?>" type="date" id="birth_date" name="birth_date" value="<?= e($me['birth_date'] ?? '') ?>" required>
            <?php if ($errors['birth_date']): ?><div class="err">⚠ <?= e($errors['birth_date']) ?></div><?php endif; ?>
          </div>
        </div>

        <div class="row-2">
          <div class="field">
            <label for="gender">Gender</label>
            <select class="select" id="gender" name="gender">
              <?php foreach (['female'=>'Female','male'=>'Male','other'=>'Other'] as $v=>$l): ?>
                <option value="<?= e($v) ?>" <?= ($me['gender'] ?? '')===$v?'selected':'' ?>><?= e($l) ?></option>
              <?php endforeach; ?>
            </select>
            <?php if ($errors['gender']): ?><div class="err">⚠ <?= e($errors['gender']) ?></div><?php endif; ?>
          </div>
          <div class="field">
            <label for="category">Community</label>
            <select class="select" id="category" name="category">
              <?php foreach (CATEGORIES as $k=>$c): ?>
                <option value="<?= e($k) ?>" <?= ($me['category'] ?? '') === $k?'selected':'' ?>><?= e($c['icon']) ?> <?= e($c['label']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="row-2">
          <div class="field">
            <label for="orientation">Orientation</label>
            <select class="select" id="orientation" name="orientation">
              <?php foreach (['flexible','straight','gay','bi','pan','ace'] as $o): ?>
                <option value="<?= e($o) ?>" <?= ($me['orientation'] ?? '') === $o?'selected':'' ?>><?= e(ucfirst($o)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="body_type">Body type</label>
            <select class="select" id="body_type" name="body_type">
              <option value="">— Not set —</option>
              <?php foreach (['lean','average','athletic','toned','stocky','fit','curvy'] as $o): ?>
                <option value="<?= e($o) ?>" <?= ($me['body_type'] ?? '') === $o?'selected':'' ?>><?= e(ucfirst($o)) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="row-2">
          <div class="field">
            <label for="city">City</label>
            <input class="input" type="text" id="city" name="city" value="<?= e($me['city'] ?? '') ?>" maxlength="60">
          </div>
          <div class="field">
            <label for="country">Country</label>
            <input class="input" type="text" id="country" name="country" value="<?= e($me['country'] ?? '') ?>" maxlength="60">
          </div>
        </div>

        <div class="row-2">
          <div class="field">
            <label for="zip">Postcode</label>
            <input class="input" type="text" id="zip" name="zip" value="<?= e($me['zip'] ?? '') ?>" maxlength="16">
          </div>
          <div class="field">
            <label for="height_cm">Height (cm)</label>
            <input class="input" type="number" id="height_cm" name="height_cm" value="<?= (int)($me['height_cm'] ?? 0) ?>" min="0" max="250" placeholder="175">
          </div>
        </div>

        <div class="field">
          <label for="bio">Bio <span class="text-muted" style="font-weight:400">(max 2000 chars)</span></label>
          <textarea class="input<?= field_has_error($errors,'bio') ?>" id="bio" name="bio" maxlength="2000" placeholder="Tell us about yourself…"><?= e($me['bio'] ?? '') ?></textarea>
          <?php if ($errors['bio']): ?><div class="err">⚠ <?= e($errors['bio']) ?></div><?php endif; ?>
        </div>

        <div class="field">
          <label for="interests">Interests <span class="text-muted" style="font-weight:400">(comma separated)</span></label>
          <input class="input" type="text" id="interests" name="interests" value="<?= e($me['interests'] ?? '') ?>" maxlength="1000" placeholder="Travel, music, cinema, hiking">
        </div>

        <div class="field">
          <label for="looking_for">Looking for</label>
          <select class="select" id="looking_for" name="looking_for">
            <option value="">— Nothing set —</option>
            <?php foreach (['friends','dating','relationship','couples','mmf','ffm','open'] as $o): ?>
              <option value="<?= e($o) ?>" <?= ($me['looking_for'] ?? '') === $o?'selected':'' ?>><?= e(ucfirst($o)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field">
          <label for="visibility">Who can see my profile</label>
          <select class="select" id="visibility" name="visibility">
            <option value="members" <?= $me['visibility'] ?? ''==='members'?'selected':'' ?>>Only signed-in members</option>
            <option value="everyone" <?= $me['visibility'] ?? ''==='everyone'?'selected':'' ?>>Everyone (indexable)</option>
          </select>
          <div class="hint">"Everyone" makes your public profile visible to logged-out visitors and search engines.</div>
        </div>

        <button class="btn btn-primary" type="submit">Save changes</button>
      </form>
    <?php endif; ?>

    <!-- ══ PHOTOS ══ -->
    <?php if ($tab === 'photos'): ?>
      <div class="row-2" style="grid-template-columns:1fr 1fr;align-items:start;gap:1.6rem">
        <div class="card card-pad">
          <h3>Profile photo</h3>
          <div style="display:flex;gap:1.2rem;align-items:center;margin-bottom:1rem">
            <div class="edit-img-wrap">
              <img class="avatar xl" id="avatarPreview" src="<?= e(avatar_url($me['avatar'] ?? '', $me['username'])) ?>" alt="Your photo" data-fb>
              <button class="img-edit-btn" type="button" onclick="openPhotoEdit('avatar', '', '<?= e(avatar_url($me['avatar'] ?? '', $me['username'])) ?>', 'Profile photo')" aria-label="Edit photo">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
              </button>
            </div>
            <div>
              <p class="text-muted" style="margin:0 0 .6rem;font-size:.85rem">JPG, PNG, WebP or GIF · max <?= MAX_AVATAR_MB ?> MB</p>
              <form method="post" action="/edit_profile.php" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="avatar">
                <input class="input" type="file" name="avatar" accept="image/*" required>
                <button class="btn btn-primary btn-sm" type="submit" style="margin-top:.6rem">Upload photo</button>
              </form>
            </div>
          </div>
          <?php if (!empty($errors['avatar'])): ?><div class="err">⚠ <?= e($errors['avatar']) ?></div><?php endif; ?>
        </div>

        <div class="card card-pad">
          <h3>Cover photo</h3>
          <div style="position:relative;border-radius:var(--r-sm);overflow:hidden;background:var(--surface-3);aspect-ratio:16/9;display:grid;place-items:center">
            <?php if ($me['cover'] ?? ''): ?>
              <img id="coverPreview" src="<?= e(avatar_url($me['cover'] ?? '', null)) ?>" alt="Cover" data-fb style="width:100%;height:100%;object-fit:cover">
              <button type="button" onclick="openPhotoEdit('cover', '', '<?= e(avatar_url($me['cover'] ?? '', null)) ?>', 'Cover photo')" aria-label="Edit cover"
                      style="position:absolute;top:.5rem;right:.5rem;width:32px;height:32px;border-radius:50%;background:rgba(0,0,0,.6);color:#fff;border:none;cursor:pointer;display:flex;align-items:center;justify-content:center">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
              </button>
            <?php else: ?>
              <span class="text-muted" style="font-size:.9rem">No cover photo</span>
            <?php endif; ?>
          </div>
          <form method="post" action="/edit_profile.php" enctype="multipart/form-data" style="margin-top:1rem">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="cover">
            <input class="input" type="file" name="cover" accept="image/*" required>
            <button class="btn btn-primary btn-sm" type="submit" style="margin-top:.6rem">Upload cover</button>
          </form>
          <?php if (!empty($errors['cover'])): ?><div class="err">⚠ <?= e($errors['cover']) ?></div><?php endif; ?>
        </div>
      </div>

      <div class="card card-pad" style="margin-top:1.6rem">
        <h3>Photo gallery</h3>
        <form method="post" action="/edit_profile.php" enctype="multipart/form-data" style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="photo_add">
          <input class="input" type="file" name="photo" accept="image/*" required style="max-width:320px">
          <button class="btn btn-primary btn-sm" type="submit">Add photo</button>
          <?php if (!empty($errors['photo'])): ?><span class="err">⚠ <?= e($errors['photo']) ?></span><?php endif; ?>
        </form>

        <?php if (empty($myPhotos)): ?>
          <div class="empty" style="padding:2rem">
            <div class="icon" aria-hidden="true">🖼</div>
            <p>You haven't added any photos yet.</p>
          </div>
        <?php else: ?>
          <div class="photolist" style="margin-top:1.4rem">
            <?php foreach ($myPhotos as $ph): ?>
              <div style="position:relative">
                <img src="<?= e(avatar_url($ph['photo'], null)) ?>" data-full="<?= e(avatar_url($ph['photo'], null)) ?>" alt="Your photo" data-fb loading="lazy">
                <button class="img-edit-btn" type="button" onclick="openPhotoEdit('photo', <?= (int)$ph['id'] ?>, '<?= e(avatar_url($ph['photo'], null)) ?>', 'Gallery photo')" aria-label="Edit photo" style="top:.3rem;right:auto;left:.3rem;width:28px;height:28px">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:14px;height:14px"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                </button>
                <form method="post" action="/edit_profile.php"
                      onsubmit="return confirm('Remove this photo?')"
                      style="position:absolute;top:.3rem;right:.3rem;margin:0">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="photo_del">
                  <input type="hidden" name="photo_id" value="<?= (int)$ph['id'] ?>">
                  <button type="submit" class="btn btn-ghost btn-sm" style="padding:.1rem .5rem;font-size:.7rem;background:rgba(0,0,0,.7)"
                          aria-label="Remove photo">✕</button>
                </form>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- ══ SETTINGS ══ -->
    <?php if ($tab === 'settings'): ?>
      <div class="row-2" style="align-items:start;gap:1.6rem">
        <div class="card card-pad">
          <h3>Change password</h3>
          <form method="post" action="/edit_profile.php" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <!-- Echo current values so the profile is not reset when only the password changes -->
            <input type="hidden" name="first_name" value="<?= e($me['first_name']) ?>">
            <input type="hidden" name="last_name" value="<?= e($me['last_name'] ?? '') ?>">
            <input type="hidden" name="username" value="<?= e($me['username']) ?>">
            <input type="hidden" name="gender" value="<?= e($me['gender']) ?>">
            <input type="hidden" name="category" value="<?= e($me['category']) ?>">
            <input type="hidden" name="orientation" value="<?= e($me['orientation'] ?? '') ?>">
            <input type="hidden" name="birth_date" value="<?= e($me['birth_date']) ?>">
            <input type="hidden" name="city" value="<?= e($me['city'] ?? '') ?>">
            <input type="hidden" name="country" value="<?= e($me['country'] ?? '') ?>">
            <input type="hidden" name="zip" value="<?= e($me['zip'] ?? '') ?>">
            <input type="hidden" name="bio" value="<?= e($me['bio'] ?? '') ?>">
            <input type="hidden" name="interests" value="<?= e($me['interests'] ?? '') ?>">
            <input type="hidden" name="looking_for" value="<?= e($me['looking_for'] ?? '') ?>">
            <input type="hidden" name="body_type" value="<?= e($me['body_type'] ?? '' ?? '') ?>">
            <input type="hidden" name="height_cm" value="<?= (int)($me['height_cm'] ?? 0) ?>">
            <input type="hidden" name="visibility" value="<?= e($me['visibility'] ?? '') ?>">

            <div class="field">
              <label for="current_password">Current password</label>
              <input class="input" type="password" id="current_password" name="current_password" autocomplete="current-password" required>
            </div>
            <div class="field">
              <label for="new_password">New password</label>
              <input class="input<?= field_has_error($errors,'new_password') ?>" type="password" id="new_password" name="new_password" minlength="8" autocomplete="new-password" placeholder="Leave blank to keep current">
              <div id="pwStrength" class="hint" aria-live="polite"></div>
              <?php if ($errors['new_password']): ?><div class="err">⚠ <?= e($errors['new_password']) ?></div><?php endif; ?>
            </div>
            <button class="btn btn-primary" type="submit">Update password</button>
          </form>
        </div>

        <div class="card card-pad" style="border-color:rgba(255,95,109,.35);background:rgba(255,95,109,.06)">
          <h3 style="color:#ffb0b8">Danger zone</h3>
          <p class="text-muted" style="font-size:.9rem">Deleting your account removes your profile, photos and messages permanently. This cannot be undone.</p>
          <form method="post" action="/edit_profile.php" novalidate onsubmit="return confirm('Delete your account permanently? Type your username to confirm.');">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete_account">
            <div class="field">
              <label for="confirm_del">Type <strong><?= e($me['username']) ?></strong> to confirm</label>
              <input class="input<?= field_has_error($errors,'delete') ?>" type="text" id="confirm_del" name="confirm" placeholder="<?= e($me['username']) ?>">
              <?php if ($errors['delete']): ?><div class="err">⚠ <?= e($errors['delete']) ?></div><?php endif; ?>
            </div>
            <button class="btn btn-danger" type="submit">Delete my account</button>
          </form>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>

<!-- Photo Edit Modal -->
<div id="photoEditModal" class="photo-edit-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.85);z-index:10000;align-items:center;justify-content:center">
  <div class="photo-edit-content" style="background:#fff;border-radius:12px;width:90%;max-width:800px;max-height:90vh;display:flex;flex-direction:column">
    <div style="display:flex;justify-content:space-between;align-items:center;padding:1rem 1.5rem;border-bottom:1px solid #e0e0e0">
      <h3 style="margin:0;color:#c44569">Edit Photo</h3>
      <button onclick="closePhotoEdit()" style="background:none;border:none;font-size:24px;cursor:pointer;color:#666">×</button>
    </div>
    
    <div style="padding:1.5rem;overflow-y:auto;flex:1">
      <div style="display:flex;gap:2rem;flex-wrap:wrap">
        <!-- Image Preview -->
        <div style="flex:1;min-width:280px">
          <div style="border:2px solid #e0e0e0;border-radius:8px;overflow:hidden;background:#f5f5f5;height:400px;display:flex;align-items:center;justify-content:center;position:relative">
            <img id="editImage" src="" alt="Edit preview" style="max-width:100%;max-height:100%;transform-origin:center center;transition:transform 0.2s, filter 0.2s">
          </div>
          
          <!-- Zoom Controls -->
          <div style="display:flex;align-items:center;justify-content:center;gap:1rem;margin-top:1rem">
            <button onclick="zoomEditImage(-0.2)" style="width:40px;height:40px;border-radius:50%;border:2px solid #c44569;background:#fff;color:#c44569;font-size:20px;cursor:pointer;display:flex;align-items:center;justify-content:center">−</button>
            <span id="editZoomLevel" style="padding:8px 16px;border:2px solid #e0e0e0;border-radius:20px;font-size:14px;color:#666;min-width:60px;text-align:center">100%</span>
            <button onclick="zoomEditImage(0.2)" style="width:40px;height:40px;border-radius:50%;border:2px solid #c44569;background:#fff;color:#c44569;font-size:20px;cursor:pointer;display:flex;align-items:center;justify-content:center">+</button>
            <button onclick="resetEditZoom()" style="padding:8px 16px;border:2px solid #e0e0e0;background:#fff;color:#666;border-radius:20px;cursor:pointer;font-size:14px">Reset</button>
          </div>
        </div>
        
        <!-- Filter Controls -->
        <div style="flex:1;min-width:280px">
          <h4 style="color:#666;margin-bottom:1rem;font-size:14px;text-transform:uppercase;letter-spacing:0.5px">Filters</h4>
          
          <div style="display:grid;gap:1rem">
            <div>
              <label style="display:flex;justify-content:space-between;font-size:13px;color:#666;margin-bottom:4px">
                <span>Brightness</span>
                <span id="brightnessVal">100%</span>
              </label>
              <input type="range" id="brightnessFilter" min="50" max="200" value="100" style="width:100%;accent-color:#c44569" oninput="applyEditFilter()">
            </div>
            
            <div>
              <label style="display:flex;justify-content:space-between;font-size:13px;color:#666;margin-bottom:4px">
                <span>Contrast</span>
                <span id="contrastVal">100%</span>
              </label>
              <input type="range" id="contrastFilter" min="50" max="200" value="100" style="width:100%;accent-color:#c44569" oninput="applyEditFilter()">
            </div>
            
            <div>
              <label style="display:flex;justify-content:space-between;font-size:13px;color:#666;margin-bottom:4px">
                <span>Saturation</span>
                <span id="saturationVal">100%</span>
              </label>
              <input type="range" id="saturationFilter" min="0" max="200" value="100" style="width:100%;accent-color:#c44569" oninput="applyEditFilter()">
            </div>
            
            <div>
              <label style="display:flex;justify-content:space-between;font-size:13px;color:#666;margin-bottom:4px">
                <span>Blur</span>
                <span id="blurVal">0px</span>
              </label>
              <input type="range" id="blurFilter" min="0" max="10" value="0" step="0.1" style="width:100%;accent-color:#c44569" oninput="applyEditFilter()">
            </div>
            
            <div>
              <label style="display:flex;justify-content:space-between;font-size:13px;color:#666;margin-bottom:4px">
                <span>Grayscale</span>
                <span id="grayscaleVal">0%</span>
              </label>
              <input type="range" id="grayscaleFilter" min="0" max="100" value="0" style="width:100%;accent-color:#c44569" oninput="applyEditFilter()">
            </div>
            
            <div>
              <label style="display:flex;justify-content:space-between;font-size:13px;color:#666;margin-bottom:4px">
                <span>Hue Rotate</span>
                <span id="hueVal">0°</span>
              </label>
              <input type="range" id="hueFilter" min="0" max="360" value="0" style="width:100%;accent-color:#c44569" oninput="applyEditFilter()">
            </div>
            
            <div>
              <label style="display:flex;justify-content:space-between;font-size:13px;color:#666;margin-bottom:4px">
                <span>Rotate</span>
                <span id="rotateVal">0°</span>
              </label>
              <input type="range" id="rotateFilter" min="-180" max="180" value="0" step="90" style="width:100%;accent-color:#c44569" oninput="applyEditFilter()">
            </div>
          </div>
          
          <button onclick="resetEditFilters()" style="margin-top:1rem;padding:10px 20px;border:2px solid #e0e0e0;background:#fff;color:#666;border-radius:8px;cursor:pointer;font-size:14px;width:100%">
            Reset All Filters
          </button>
        </div>
      </div>
    </div>
    
    <!-- Footer -->
    <div style="display:flex;justify-content:flex-end;gap:1rem;padding:1rem 1.5rem;border-top:1px solid #e0e0e0">
      <button onclick="closePhotoEdit()" style="padding:10px 20px;border:2px solid #e0e0e0;background:#fff;color:#666;border-radius:8px;cursor:pointer;font-size:14px">Cancel</button>
      <button id="saveEditBtn" onclick="saveEditedPhoto()" style="padding:10px 20px;border:none;background:#c44569;color:#fff;border-radius:8px;cursor:pointer;font-size:14px">
        Save Changes
      </button>
    </div>
  </div>
</div>

<script>
  let editZoomLevel = 100;
  let currentEditAction = '';
  let currentEditPhotoId = '';
  let currentEditOriginalSrc = '';
  
  function openPhotoEdit(action, photoId, imgSrc, imgAlt) {
    currentEditAction = action;
    currentEditPhotoId = photoId || '';
    currentEditOriginalSrc = imgSrc;
    
    const img = document.getElementById('editImage');
    img.src = imgSrc;
    img.alt = imgAlt || 'Edit preview';
    
    resetEditZoom();
    resetEditFilters();
    
    document.getElementById('photoEditModal').style.display = 'flex';
  }
  
  function closePhotoEdit() {
    document.getElementById('photoEditModal').style.display = 'none';
  }
  
  function zoomEditImage(delta) {
    editZoomLevel += (delta * 100);
    editZoomLevel = Math.max(50, Math.min(300, editZoomLevel));
    
    const img = document.getElementById('editImage');
    img.style.transform = `scale(${editZoomLevel / 100}) rotate(${document.getElementById('rotateFilter').value}deg)`;
    
    document.getElementById('editZoomLevel').textContent = Math.round(editZoomLevel) + '%';
  }
  
  function resetEditZoom() {
    editZoomLevel = 100;
    const img = document.getElementById('editImage');
    img.style.transform = `scale(1) rotate(${document.getElementById('rotateFilter').value}deg)`;
    document.getElementById('editZoomLevel').textContent = '100%';
  }
  
  function applyEditFilter() {
    const brightness = document.getElementById('brightnessFilter').value;
    const contrast = document.getElementById('contrastFilter').value;
    const saturation = document.getElementById('saturationFilter').value;
    const blur = document.getElementById('blurFilter').value;
    const grayscale = document.getElementById('grayscaleFilter').value;
    const hue = document.getElementById('hueFilter').value;
    const rotate = document.getElementById('rotateFilter').value;
    
    document.getElementById('brightnessVal').textContent = brightness + '%';
    document.getElementById('contrastVal').textContent = contrast + '%';
    document.getElementById('saturationVal').textContent = saturation + '%';
    document.getElementById('blurVal').textContent = blur + 'px';
    document.getElementById('grayscaleVal').textContent = grayscale + '%';
    document.getElementById('hueVal').textContent = hue + '°';
    document.getElementById('rotateVal').textContent = rotate + '°';
    
    const img = document.getElementById('editImage');
    img.style.transform = `scale(${editZoomLevel / 100}) rotate(${rotate}deg)`;
    img.style.filter = `brightness(${brightness}%) contrast(${contrast}%) saturate(${saturation}%) blur(${blur}px) grayscale(${grayscale}%) hue-rotate(${hue}deg)`;
  }
  
  function resetEditFilters() {
    document.getElementById('brightnessFilter').value = 100;
    document.getElementById('contrastFilter').value = 100;
    document.getElementById('saturationFilter').value = 100;
    document.getElementById('blurFilter').value = 0;
    document.getElementById('grayscaleFilter').value = 0;
    document.getElementById('hueFilter').value = 0;
    document.getElementById('rotateFilter').value = 0;
    
    applyEditFilter();
  }
  
  function saveEditedPhoto() {
    // Save the edited photo by submitting the form
    const brightness = document.getElementById('brightnessFilter').value;
    const contrast = document.getElementById('contrastFilter').value;
    const saturation = document.getElementById('saturationFilter').value;
    const blur = document.getElementById('blurFilter').value;
    const grayscale = document.getElementById('grayscaleFilter').value;
    const hue = document.getElementById('hueFilter').value;
    const rotate = document.getElementById('rotateFilter').value;
    
    // Create a form to submit the filter values
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/edit_profile.php';
    
    // Add hidden fields
    const fields = [
      ['action', currentEditAction === 'avatar' ? 'avatar_edit' : currentEditAction === 'cover' ? 'cover_edit' : 'photo_edit'],
      ['photo_id', currentEditPhotoId],
      ['brightness', brightness],
      ['contrast', contrast],
      ['saturation', saturation],
      ['blur', blur],
      ['grayscale', grayscale],
      ['hue', hue],
      ['rotate', rotate]
    ];
    
    fields.forEach(([name, value]) => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = name;
      input.value = value;
      form.appendChild(input);
    });
    
    // Add CSRF token
    const csrf = document.querySelector('input[name="csrf"]');
    if (csrf) {
      const csrfInput = document.createElement('input');
      csrfInput.type = 'hidden';
      csrfInput.name = 'csrf';
      csrfInput.value = csrf.value;
      form.appendChild(csrfInput);
    }
    
    document.body.appendChild(form);
    form.submit();
  }
  
  // Close modal on outside click
  document.getElementById('photoEditModal').addEventListener('click', function(e) {
    if (e.target === this) closePhotoEdit();
  });
</script>
