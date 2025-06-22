<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

$errors = [];
$success = false;

// Business types for dropdown
$businessTypes = [];
try {
    $businessTypes = get_business_types();
} catch(PDOException $e) {
    $errors[] = "Error loading business types: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    registration();
}

// African countries with phone codes
$africanCountries = [
    'DZ' => ['name' => 'Algeria', 'phone_code' => '+213'],
    'AO' => ['name' => 'Angola', 'phone_code' => '+244'],
    'BJ' => ['name' => 'Benin', 'phone_code' => '+229'],
    'BW' => ['name' => 'Botswana', 'phone_code' => '+267'],
    'BF' => ['name' => 'Burkina Faso', 'phone_code' => '+226'],
    'BI' => ['name' => 'Burundi', 'phone_code' => '+257'],
    'CV' => ['name' => 'Cabo Verde', 'phone_code' => '+238'],
    'CM' => ['name' => 'Cameroon', 'phone_code' => '+237'],
    'CF' => ['name' => 'Central African Republic', 'phone_code' => '+236'],
    'TD' => ['name' => 'Chad', 'phone_code' => '+235'],
    'KM' => ['name' => 'Comoros', 'phone_code' => '+269'],
    'CG' => ['name' => 'Congo', 'phone_code' => '+242'],
    'CD' => ['name' => 'Congo, Democratic Republic', 'phone_code' => '+243'],
    'CI' => ['name' => "Côte d'Ivoire", 'phone_code' => '+225'],
    'DJ' => ['name' => 'Djibouti', 'phone_code' => '+253'],
    'EG' => ['name' => 'Egypt', 'phone_code' => '+20'],
    'GQ' => ['name' => 'Equatorial Guinea', 'phone_code' => '+240'],
    'ER' => ['name' => 'Eritrea', 'phone_code' => '+291'],
    'SZ' => ['name' => 'Eswatini', 'phone_code' => '+268'],
    'ET' => ['name' => 'Ethiopia', 'phone_code' => '+251'],
    'GA' => ['name' => 'Gabon', 'phone_code' => '+241'],
    'GM' => ['name' => 'Gambia', 'phone_code' => '+220'],
    'GH' => ['name' => 'Ghana', 'phone_code' => '+233'],
    'GN' => ['name' => 'Guinea', 'phone_code' => '+224'],
    'GW' => ['name' => 'Guinea-Bissau', 'phone_code' => '+245'],
    'KE' => ['name' => 'Kenya', 'phone_code' => '+254'],
    'LS' => ['name' => 'Lesotho', 'phone_code' => '+266'],
    'LR' => ['name' => 'Liberia', 'phone_code' => '+231'],
    'LY' => ['name' => 'Libya', 'phone_code' => '+218'],
    'MG' => ['name' => 'Madagascar', 'phone_code' => '+261'],
    'MW' => ['name' => 'Malawi', 'phone_code' => '+265'],
    'ML' => ['name' => 'Mali', 'phone_code' => '+223'],
    'MR' => ['name' => 'Mauritania', 'phone_code' => '+222'],
    'MU' => ['name' => 'Mauritius', 'phone_code' => '+230'],
    'YT' => ['name' => 'Mayotte', 'phone_code' => '+262'],
    'MA' => ['name' => 'Morocco', 'phone_code' => '+212'],
    'MZ' => ['name' => 'Mozambique', 'phone_code' => '+258'],
    'NA' => ['name' => 'Namibia', 'phone_code' => '+264'],
    'NE' => ['name' => 'Niger', 'phone_code' => '+227'],
    'NG' => ['name' => 'Nigeria', 'phone_code' => '+234'],
    'RE' => ['name' => 'Réunion', 'phone_code' => '+262'],
    'RW' => ['name' => 'Rwanda', 'phone_code' => '+250'],
    'SH' => ['name' => 'Saint Helena', 'phone_code' => '+290'],
    'ST' => ['name' => 'Sao Tome and Principe', 'phone_code' => '+239'],
    'SN' => ['name' => 'Senegal', 'phone_code' => '+221'],
    'SC' => ['name' => 'Seychelles', 'phone_code' => '+248'],
    'SL' => ['name' => 'Sierra Leone', 'phone_code' => '+232'],
    'SO' => ['name' => 'Somalia', 'phone_code' => '+252'],
    'ZA' => ['name' => 'South Africa', 'phone_code' => '+27'],
    'SS' => ['name' => 'South Sudan', 'phone_code' => '+211'],
    'SD' => ['name' => 'Sudan', 'phone_code' => '+249'],
    'TZ' => ['name' => 'Tanzania', 'phone_code' => '+255'],
    'TG' => ['name' => 'Togo', 'phone_code' => '+228'],
    'TN' => ['name' => 'Tunisia', 'phone_code' => '+216'],
    'UG' => ['name' => 'Uganda', 'phone_code' => '+256'],
    'EH' => ['name' => 'Western Sahara', 'phone_code' => '+212'],
    'ZM' => ['name' => 'Zambia', 'phone_code' => '+260'],
    'ZW' => ['name' => 'Zimbabwe', 'phone_code' => '+263']
];

// Cities by country (sample data - in a real app you'd want more comprehensive data)
$citiesByCountry = [
    'NG' => ['Lagos', 'Abuja', 'Kano', 'Ibadan', 'Port Harcourt', 'Benin City', 'Maiduguri'],
    'KE' => ['Nairobi', 'Mombasa', 'Kisumu', 'Nakuru', 'Eldoret'],
    'ZA' => ['Johannesburg', 'Cape Town', 'Durban', 'Pretoria', 'Port Elizabeth'],
    'EG' => ['Cairo', 'Alexandria', 'Giza', 'Shubra El-Kheima', 'Port Said'],
    'GH' => ['Accra', 'Kumasi', 'Tamale', 'Sekondi-Takoradi', 'Sunyani'],
    'NA' => ['Windhoek'],
    // Add more countries and cities as needed
];

// Default to Nigeria if no country selected
$selectedCountry = $_POST['country'] ?? 'NA';
$selectedPhoneCode = $africanCountries[$selectedCountry]['phone_code'] ?? '+264';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Registration | <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h2 class="h4">Business Registration</h2>
                    </div>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            Registration successful! Your business is under review. You'll be notified once approved.
                        </div>
                    <?php elseif (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul>
                                <?php foreach ($errors as $error): ?>
                                    <li><? echo $error ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <?php if (!$success): ?>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <h3 class="h5 mb-3">Account Information</h3>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="username" class="form-label">Username*</label>
                                    <input type="text" class="form-control" id="username" name="username" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email*</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="password" class="form-label">Password* (min 8 characters)</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label">Confirm Password*</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>

                            <hr class="my-4">

                            <h3 class="h5 mb-3">Business Information</h3>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="business_name" class="form-label">Business Name*</label>
                                    <input type="text" class="form-control" id="business_name" name="business_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="business_type" class="form-label">Business Type*</label>
                                    <select class="form-select" id="business_type" name="business_type" required>
                                        <option value="">Select Business Type</option>
                                        <?php foreach ($businessTypes as $type): ?>
                                            <option value="<?= $type['type_id'] ?>"><?= htmlspecialchars($type['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="business_description" class="form-label">Business Description</label>
                                    <textarea class="form-control" id="business_description" name="business_description" rows="3"></textarea>
                                </div>
                            </div>

                            <hr class="my-4">

                            <h3 class="h5 mb-3">Business Address</h3>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="address_line1" class="form-label">Address Line 1*</label>
                                    <input type="text" class="form-control" id="address_line1" name="address_line1" required>
                                </div>
                                <div class="col-12">
                                    <label for="address_line2" class="form-label">Address Line 2</label>
                                    <input type="text" class="form-control" id="address_line2" name="address_line2">
                                </div>
                                <div class="col-md-4">
                                    <label for="country" class="form-label">Country*</label>
                                    <select class="form-select" id="country" name="country" required onchange="updateCitiesAndPhoneCode()">
                                        <option value="">Select Country</option>
                                        <?php foreach ($africanCountries as $code => $country): ?>
                                            <option value="<?= $code ?>" <?= $selectedCountry === $code ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($country['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="city" class="form-label">City*</label>
                                    <select class="form-select" id="city" name="city" required>
                                        <?php if (isset($citiesByCountry[$selectedCountry])): ?>
                                            <?php foreach ($citiesByCountry[$selectedCountry] as $city): ?>
                                                <option value="<?= htmlspecialchars($city) ?>" <?= ($_POST['city'] ?? '') === $city ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($city) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="state" class="form-label">State/Province*</label>
                                    <input type="text" class="form-control" id="state" name="state" required>
                                </div>
                                <div class="col-md-2">
                                    <label for="postal_code" class="form-label">Postal Code*</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" required>
                                </div>
                            </div>

                            <hr class="my-4">

                            <h3 class="h5 mb-3">Admin Information</h3>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label">First Name*</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Last Name*</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="phone-code"><?= $selectedPhoneCode ?></span>
                                        <input type="tel" class="form-control" id="phone" name="phone" aria-describedby="phone-code">
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <h3 class="h5 mb-3">Business Documents</h3>
                            <div id="document-fields">
                                <div class="document-field mb-3">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Document Type</label>
                                            <select class="form-select" name="document_types[]">
                                                <option value="license">Business License</option>
                                                <option value="tax_certificate">Tax Certificate</option>
                                                <option value="id_proof">ID Proof</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">File Upload</label>
                                            <input type="file" class="form-control" name="documents[]">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="add-document">Add Another Document</button>

                            <hr class="my-4">

                            <button type="submit" class="btn btn-primary btn-lg">Submit Registration</button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add more document fields
        document.getElementById('add-document').addEventListener('click', function() {
            const container = document.getElementById('document-fields');
            const newField = document.querySelector('.document-field').cloneNode(true);
            container.appendChild(newField);
        });

        // Country data for JavaScript
        const africanCountries = <?= json_encode($africanCountries) ?>;
        const citiesByCountry = <?= json_encode($citiesByCountry) ?>;

        function updateCitiesAndPhoneCode() {
            const countrySelect = document.getElementById('country');
            const citySelect = document.getElementById('city');
            const phoneCodeSpan = document.getElementById('phone-code');
            
            const countryCode = countrySelect.value;
            
            // Update phone code
            if (countryCode && africanCountries[countryCode]) {
                phoneCodeSpan.textContent = africanCountries[countryCode].phone_code;
            } else {
                phoneCodeSpan.textContent = '+XXX';
            }
            
            // Update cities
            citySelect.innerHTML = '';
            if (countryCode && citiesByCountry[countryCode]) {
                citiesByCountry[countryCode].forEach(city => {
                    const option = document.createElement('option');
                    option.value = city;
                    option.textContent = city;
                    citySelect.appendChild(option);
                });
            } else {
                // Fallback if no cities defined for the country
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'Select City';
                citySelect.appendChild(option);
            }
        }
    </script>
</body>
</html>