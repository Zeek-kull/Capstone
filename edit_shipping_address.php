<?php
ob_start();
session_start();
// detect AJAX fragment requests (e.g. ?ajax=1)
$isAjax = (isset($_GET['ajax']) && $_GET['ajax'] === '1');
// Always include DB connection; include header only when rendering full page
include 'lib/connection.php';
if (!$isAjax) {
  include 'header.php';
}

// Require user to be logged in
if (!isset($_SESSION['auth']) || $_SESSION['auth'] != 1) {
    header('Location: login.php');
    exit;
}

$userId = intval($_SESSION['userid']);

// Handle POST update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_address_btn'])) {
  $street = trim($_POST['street'] ?? '');
  $zone = trim($_POST['zone'] ?? '');
  $barangay = trim($_POST['barangay_text'] ?? $_POST['barangay'] ?? '');
  $city = trim($_POST['city_text'] ?? $_POST['city'] ?? '');
  $province = trim($_POST['province_text'] ?? $_POST['province'] ?? '');
  $region = trim($_POST['region_text'] ?? '');
  $phoneRaw = trim($_POST['phone'] ?? '');

  // Normalize phone to digits only
  $phoneDigits = preg_replace('/[^0-9]/', '', $phoneRaw);

  // Validation - require full address fields similar to register.php
  $errors = [];
  if ($region === '' || $province === '' || $city === '' || $barangay === '' || $street === '') {
    $errors[] = 'Please complete all address fields.';
  }
  if ($phoneDigits === '') {
    $errors[] = 'Phone number is required.';
  } elseif (strlen($phoneDigits) !== 11 || !preg_match('/^09\d{9}$/', $phoneDigits)) {
    $errors[] = 'Phone number must be 11 digits and start with 09.';
  }

  if (count($errors) === 0) {
    $streetEsc = mysqli_real_escape_string($conn, $street);
    $zoneEsc = mysqli_real_escape_string($conn, $zone);
    $barangayEsc = mysqli_real_escape_string($conn, $barangay);
    $cityEsc = mysqli_real_escape_string($conn, $city);
    $provinceEsc = mysqli_real_escape_string($conn, $province);
    $regionEsc = mysqli_real_escape_string($conn, $region);
    $phoneEsc = mysqli_real_escape_string($conn, $phoneDigits);

    $sql = "UPDATE users SET region = '{$regionEsc}', province = '{$provinceEsc}', city = '{$cityEsc}', barangay = '{$barangayEsc}', street = '{$streetEsc}', zone = '{$zoneEsc}', phone = '{$phoneEsc}' WHERE u_id = '{$userId}'";
    $res = mysqli_query($conn, $sql);
    if ($res) {
      // Success
      if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'message' => 'Shipping address updated successfully.']);
        exit;
      }
      $_SESSION['success_message'] = 'Shipping address updated successfully.';
      header('Location: profile.php');
      exit;
    } else {
      // DB error - capture details for debugging
      $dberr = mysqli_error($conn);
      if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to update address.', 'error' => $dberr]);
        exit;
      }
      $_SESSION['error_message'] = 'Failed to update address. DB error: ' . $dberr;
    }
  } else {
    // Join errors and show via session so includes/flash.php can display
    $_SESSION['error_message'] = implode(' ', $errors);
  }
  // After handling POST, redirect to avoid form resubmission (if not redirected already)
  header('Location: edit_shipping_address.php');
  exit;
}

$user_q = mysqli_query($conn, "SELECT f_name, l_name, region, street, zone, barangay, city, province, phone FROM users WHERE u_id = '{$userId}' LIMIT 1");
$user = mysqli_fetch_assoc($user_q) ?: [];

?>
<?php if (!$isAjax): ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Shipping Address</title>
  <link rel="stylesheet" href="css/css.css" type="text/css">
  <link rel="stylesheet" href="css/bootstrap.min.css" type="text/css">
</head>
<body class="hide-header">
<?php endif; ?>

<div class="container mt-4">
  <h4>Edit Shipping Address</h4>
  <?php include __DIR__ . '/includes/flash.php'; ?>

  <form method="post" class="border p-3 rounded" id="editAddressForm" novalidate>
    <fieldset class="form-group border p-3 mb-3">
      <legend class="w-auto px-2">Address</legend>
      <div class="form-group mb-2">
        <label for="street">Street Number/House Number</label>
        <input type="text" class="form-control" id="street" name="street" placeholder="e.g. 123 Main St" autocomplete="address-line1" value="<?php echo htmlspecialchars($user['street'] ?? ''); ?>" required>
      </div>
      <div class="form-group mb-2">
        <label for="zone">Zone/Purok (optional)</label>
        <input type="text" class="form-control" id="zone" name="zone" placeholder="e.g. Zone 1" autocomplete="address-level4" value="<?php echo htmlspecialchars($user['zone'] ?? ''); ?>">
      </div>

      <div class="form-group mb-2">
        <label for="region">Region</label>
        <select id="region" class="form-control" required>
          <option value=""><?php echo isset($user['province']) ? 'Select Region' : 'Select Region'; ?></option>
        </select>
        <input type="hidden" name="region_text" id="region-text" value="<?php echo htmlspecialchars($user['region'] ?? ''); ?>">
      </div>

      <div class="form-group mb-2">
        <label for="province">Province</label>
        <select id="province" class="form-control" required>
          <option value=""><?php echo isset($user['province']) ? 'Select Province' : 'Select Province'; ?></option>
        </select>
        <input type="hidden" name="province_text" id="province-text" value="<?php echo htmlspecialchars($user['province'] ?? ''); ?>">
      </div>

      <div class="form-group mb-2">
        <label for="city">City/Municipality</label>
        <select id="city" class="form-control" required>
          <option value=""><?php echo isset($user['city']) ? 'Select City' : 'Select City'; ?></option>
        </select>
        <input type="hidden" name="city_text" id="city-text" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
      </div>

      <div class="form-group mb-2">
        <label for="barangay">Barangay</label>
        <select id="barangay" class="form-control" required>
          <option value=""><?php echo isset($user['barangay']) ? 'Select Barangay' : 'Select Barangay'; ?></option>
        </select>
        <input type="hidden" name="barangay_text" id="barangay-text" value="<?php echo htmlspecialchars($user['barangay'] ?? ''); ?>">
      </div>

      <div id="address-error" class="text-danger mb-2" style="display:none;"></div>
    </fieldset>

    <div class="form-group">
      <label for="phone">Phone Number</label>
      <input type="tel" class="form-control" id="phone" name="phone" placeholder="09123456789" maxlength="11" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
      <small class="form-text text-muted">Format: 09123456789 (11 digits starting with 09)</small>
    </div>

    <div class="mt-3">
      <button type="submit" name="update_address_btn" class="btn btn-primary">Save Address</button>
      <?php if ($isAjax): ?>
        <button type="button" class="btn btn-secondary" id="addressEditCancelBtn">Cancel</button>
      <?php else: ?>
        <a href="profile.php" class="btn btn-secondary">Cancel</a>
      <?php endif; ?>
    </div>
  </form>
</div>

<?php if (!$isAjax) { include 'footer.php'; ?>
  <!-- JS Plugins -->
  <script src="js/jquery-3.6.0.min.js"></script>
  <script src="js/bootstrap.min.js"></script>
  <script src="js/ph-address-selector.js"></script>
  <script src="js/main.bundle.js"></script>
  <script>
    // initialize address selector after page load using hidden values
    function initAddressSelects() {
      if (typeof jQuery === 'undefined') return;
      var $ = jQuery;

      // helper to select option by visible text
      function selectByText($select, text) {
        if (!text) return;
        var opt = $select.find('option').filter(function(){ return $(this).text().trim() === text.trim(); }).first();
        if (opt.length) { $select.val(opt.val()).trigger('change'); }
      }

      // bind handlers (use existing my_handlers if present)
      try {
        if (typeof my_handlers !== 'undefined') {
          $('#region').off('change').on('change', my_handlers.fill_provinces);
          $('#province').off('change').on('change', my_handlers.fill_cities);
          $('#city').off('change').on('change', my_handlers.fill_barangays);
          $('#barangay').off('change').on('change', my_handlers.onchange_barangay);
        }
      } catch(e){}

      // populate regions (same source used by ph-address-selector.js)
      $.getJSON('ph-json/region.json', function(regions){
        var $region = $('#region');
        $region.empty().append('<option selected="true" disabled>Choose Region</option>');
        $.each(regions, function(i, r){
          $region.append($('<option>').val(r.region_code).text(r.region_name));
        });

          // try to preselect region by hidden text
          var regionText = $('#region-text').val();
          if (regionText) selectByText($region, regionText);

          // helper loaders: province -> city -> barangay
          function loadProvinces(regionCode, cb) {
            $.getJSON('ph-json/province.json', function(provinces){
              var $prov = $('#province');
              $prov.empty().append('<option selected="true" disabled>Choose State/Province</option>');
              var list = provinces.filter(function(p){ return p.region_code == regionCode; });
              list.sort(function(a,b){ return a.province_name.localeCompare(b.province_name); });
              $.each(list, function(i,p){ $prov.append($('<option>').val(p.province_code).text(p.province_name)); });
              if (cb) cb();
            });
          }

          function loadCities(provinceCode, cb) {
            $.getJSON('ph-json/city.json', function(cities){
              var $city = $('#city');
              $city.empty().append('<option selected="true" disabled>Choose city/municipality</option>');
              var list = cities.filter(function(c){ return c.province_code == provinceCode; });
              list.sort(function(a,b){ return a.city_name.localeCompare(b.city_name); });
              $.each(list, function(i,c){ $city.append($('<option>').val(c.city_code).text(c.city_name)); });
              if (cb) cb();
            });
          }

          function loadBarangays(cityCode, cb) {
            $.getJSON('ph-json/barangay.json', function(barangays){
              var $brgy = $('#barangay');
              $brgy.empty().append('<option selected="true" disabled>Choose barangay</option>');
              var list = barangays.filter(function(b){ return b.city_code == cityCode; });
              list.sort(function(a,b){ return a.brgy_name.localeCompare(b.brgy_name); });
              $.each(list, function(i,b){ $brgy.append($('<option>').val(b.brgy_code).text(b.brgy_name)); });
              if (cb) cb();
            });
          }

          // If region has been preselected (or user chooses), chain the loaders and preselect by visible text values
          function chainPreselect() {
            var rCode = $('#region').val();
            if (!rCode) return;
            var wantedProvince = $('#province-text').val();
            var wantedCity = $('#city-text').val();
            var wantedBarangay = $('#barangay-text').val();
            loadProvinces(rCode, function(){
              if (wantedProvince) {
                selectByText($('#province'), wantedProvince);
              }
              var pCode = $('#province').val();
              if (!pCode) {
                // try to match by text to find code
                var match = $('#province').find('option').filter(function(){ return $(this).text().trim() === (wantedProvince || '').trim(); }).first();
                if (match.length) pCode = match.val();
              }
              if (pCode) {
                loadCities(pCode, function(){
                  if (wantedCity) selectByText($('#city'), wantedCity);
                  var cCode = $('#city').val();
                  if (!cCode) {
                    var matchc = $('#city').find('option').filter(function(){ return $(this).text().trim() === (wantedCity || '').trim(); }).first();
                    if (matchc.length) cCode = matchc.val();
                  }
                  if (cCode) {
                    loadBarangays(cCode, function(){
                      if (wantedBarangay) selectByText($('#barangay'), wantedBarangay);
                    });
                  }
                });
              }
            });
          }

          // wire change events to load dependent selects when user interacts
          $('#region').on('change', function(){ var v = $(this).val(); if (v) { loadProvinces(v); } });
          $('#province').on('change', function(){ var v = $(this).val(); if (v) { loadCities(v); } });
          $('#city').on('change', function(){ var v = $(this).val(); if (v) { loadBarangays(v); } });

          // run chain preselect if region was selected (or after we've set region by text)
          setTimeout(chainPreselect, 50);
      });
    }

    document.addEventListener('DOMContentLoaded', initAddressSelects);

    // client-side validation (same rules as register)
    document.getElementById('editAddressForm').addEventListener('submit', function(e){
      var street = document.getElementById('street').value.trim();
      var region = document.getElementById('region').value;
      var province = document.getElementById('province').value;
      var city = document.getElementById('city').value;
      var barangay = document.getElementById('barangay').value;
      var phone = document.getElementById('phone').value.replace(/[^0-9]/g, '');
      var error = '';
      if (!region || !province || !city || !barangay || !street) {
        error = 'Please complete all address fields.';
      }
      if (!error) {
        if (phone.length !== 11) {
          error = 'Phone number must be exactly 11 digits.';
        } else if (!/^09\d{9}$/.test(phone)) {
          error = "Phone number must start with '09' and be 11 digits.";
        }
      }
      if (error) {
        document.getElementById('address-error').textContent = error;
        document.getElementById('address-error').style.display = 'block';
        e.preventDefault();
      }
    });

    // phone formatting helper
    var phoneInput = document.getElementById('phone');
    if (phoneInput) {
      phoneInput.addEventListener('input', function(e){
        var v = e.target.value.replace(/[^0-9]/g, '');
        if (v.length > 11) v = v.substring(0,11);
        e.target.value = v;
      });
    }
  </script>
<?php } else { ?>
  <!-- Fragment mode: include a small script to initialize the address selector and wire Cancel -->
  <!-- ensure ph-address-selector is available for fragment loads -->
  <script src="js/jquery-3.6.0.min.js"></script>
  <script src="js/ph-address-selector.js"></script>
  <script>
    (function(){
        // Fragment-mode: populate region/province/city/barangay selects using jQuery
        function fragmentInitAddressSelects() {
          if (typeof jQuery === 'undefined') return;
          var $ = jQuery;

          function selectByText($select, text) {
            if (!text) return;
            var opt = $select.find('option').filter(function(){ return $(this).text().trim() === text.trim(); }).first();
            if (opt.length) { $select.val(opt.val()).trigger('change'); }
          }

          try {
            if (typeof my_handlers !== 'undefined') {
              $('#region').off('change').on('change', my_handlers.fill_provinces);
              $('#province').off('change').on('change', my_handlers.fill_cities);
              $('#city').off('change').on('change', my_handlers.fill_barangays);
              $('#barangay').off('change').on('change', my_handlers.onchange_barangay);
            }
          } catch(e){}

          $.getJSON('ph-json/region.json', function(regions){
            var $region = $('#region');
            $region.empty().append('<option selected="true" disabled>Choose Region</option>');
            $.each(regions, function(i, r){
              $region.append($('<option>').val(r.region_code).text(r.region_name));
            });

            var regionText = $('#region-text').val();
            if (regionText) selectByText($region, regionText);

            // small chain to try preselecting province -> city -> barangay using hidden text values
            setTimeout(function(){
              selectByText($('#province'), $('#province-text').val());
              setTimeout(function(){
                selectByText($('#city'), $('#city-text').val());
                setTimeout(function(){
                  selectByText($('#barangay'), $('#barangay-text').val());
                }, 200);
              }, 200);
            }, 400);
          });
        }

        fragmentInitAddressSelects();

      // Cancel button closes overlay (preferred via postMessage)
      document.addEventListener('click', function(e){
        if (e.target && e.target.id === 'addressEditCancelBtn') {
          try { window.parent && window.parent.postMessage && window.parent.postMessage({ action: 'closeAddressEdit' }, '*'); } catch(err){}
          var ov = document.getElementById('addressEditOverlay'); if (ov) ov.style.display = 'none';
        }
      });

      // basic client-side validation similar to full page
      var form = document.getElementById('editAddressForm');
      if (form) {
        form.addEventListener('submit', function(e){
          e.preventDefault();
          var street = document.getElementById('street').value.trim();
          var region = document.getElementById('region').value;
          var province = document.getElementById('province').value;
          var city = document.getElementById('city').value;
          var barangay = document.getElementById('barangay').value;
          var phone = document.getElementById('phone').value.replace(/[^0-9]/g, '');
          var error = '';
          if (!region || !province || !city || !barangay || !street) {
            error = 'Please complete all address fields.';
          }
          if (!error) {
            if (phone.length !== 11) {
              error = 'Phone number must be exactly 11 digits.';
            } else if (!/^09\d{9}$/.test(phone)) {
              error = "Phone number must start with '09' and be 11 digits.";
            }
          }
          var ae = document.getElementById('address-error');
          if (error) {
            if (ae) { ae.textContent = error; ae.style.display = 'block'; }
            return;
          } else if (ae) { ae.style.display = 'none'; }

          // Build FormData and submit via fetch to AJAX endpoint
          var fd = new FormData(form);
          // ensure the update button name is present
          if (!fd.has('update_address_btn')) fd.append('update_address_btn', '1');
          // Post to edit_shipping_address.php?ajax=1
          fetch('edit_shipping_address.php?ajax=1', { method: 'POST', body: fd, credentials: 'same-origin' })
            .then(function(r){ return r.json(); })
            .then(function(json){
              if (json && json.success) {
                // update parent cart address and phone if available
                try {
                  var parentDoc = window.parent && window.parent.document ? window.parent.document : document;
                  var addrEl = parentDoc.getElementById('addressInput');
                  var phoneEl = parentDoc.getElementById('phoneInput');
                  var parts = [];
                  if (street) parts.push(street);
                  if (document.getElementById('zone') && document.getElementById('zone').value) parts.push(document.getElementById('zone').value);
                  if (barangay) parts.push(barangay);
                  var second = [];
                  if (city) second.push(city);
                  if (province) second.push(province);
                  var fullAddr = parts.join(' ');
                  if (second.length) fullAddr += ', ' + second.join(', ');
                  if (addrEl) addrEl.value = fullAddr;
                  if (phoneEl) phoneEl.value = phone;
                } catch(e){}

                // close overlay via postMessage (parent handles close) or hide local overlay
                try { window.parent && window.parent.postMessage && window.parent.postMessage({ action: 'closeAddressEdit' }, '*'); } catch(e){}
                var ov = document.getElementById('addressEditOverlay'); if (ov) ov.style.display = 'none';
                // show inline success
                if (ae) { ae.textContent = json.message || 'Address saved'; ae.style.display = 'block'; ae.style.color = 'green'; }
              } else {
                var msg = (json && json.error) ? json.error : (json && json.message) ? json.message : 'Failed to save address';
                if (ae) { ae.textContent = msg; ae.style.display = 'block'; ae.style.color = 'red'; }
              }
            }).catch(function(err){
              if (ae) { ae.textContent = 'Network or server error while saving address.'; ae.style.display = 'block'; ae.style.color = 'red'; }
            });
        });
      }
    })();
  </script>
<?php } ?>
