<?php
include('config.php');

if (!isset($_GET['id'])) {
    echo "No application selected.";
    exit;
}

$id = intval($_GET['id']);
$data = null;

// Fetch application data
if ($conn) {
    $stmt = $conn->prepare("SELECT w.*, e.booking_type, e.book_date, e.start_time, e.status, w.decline_reason
                            FROM wedding_applications w
                            LEFT JOIN event e ON w.wedding_applications_id = e.wedding_application_id
                            WHERE w.wedding_applications_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();
}

if (!$data) {
    echo "Application not found.";
    exit;
}

// Fetch already booked time slots for the specific date
$bookedSlots = [];
if ($conn) {
    $stmt = $conn->prepare("SELECT book_date, start_time FROM event WHERE start_time IN ('09:00:00', '13:00:00')");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $bookedSlots[$row['book_date']][] = $row['start_time'];
    }
    $stmt->close();
}

// Prepare booked slots for use in JavaScript
$bookedSlotsJson = json_encode($bookedSlots);

// Helper function to display uploaded files
function renderFileField($label, $path) {
    if (!$path) return;
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    echo "<li><strong>$label:</strong><br>";
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        echo "<img src=\"$path\" alt=\"$label\" style=\"max-width: 400px; height: auto; border: 1px solid #ccc; margin-bottom: 10px;\">";
    } else {
        echo "<a href=\"$path\" target=\"_blank\">View File</a>";
    }
    echo "</li>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Wedding Application Details</title>
  <link rel="stylesheet" href="styles/test-admin.css">
  <link rel="stylesheet" href="test1.css">
  <style>
    body { font-family: Arial, sans-serif; margin: 30px; }
    h2 { margin-bottom: 20px; }
    ul { list-style: none; padding: 0; }
    ul li { margin-bottom: 20px; }
    ul li img { display: block; margin-top: 10px; border-radius: 8px; }
    a.button {
      display: inline-block;
      background-color: #ba5d5d;
      color: #fff;
      padding: 8px 16px;
      border: none;
      border-radius: 5px;
      text-decoration: none;
      margin-bottom: 20px;
    }
  </style>
</head>
<body>

<a href="declined.book.user.php" class="button">← Back to List</a>

<h2>Wedding Application Details</h2>
<ul><br>
  <li><strong>Wife:</strong> <?= htmlspecialchars($data['wife_first_name'] . ' ' . $data['wife_middle_name'] . ' ' . $data['wife_last_name']) ?></li>
  <li><strong>Husband:</strong> <?= htmlspecialchars($data['husband_first_name'] . ' ' . $data['husband_middle_name'] . ' ' . $data['husband_last_name']) ?></li>
  <li><strong>Wife Age:</strong> <?= htmlspecialchars($data['wife_age']) ?></li>
  <li><strong>Husband Age:</strong> <?= htmlspecialchars($data['husband_age']) ?></li>
  <li><strong>Event Type:</strong> <?= htmlspecialchars($data['booking_type']) ?></li>
  <li><strong>Event Date:</strong> <?= htmlspecialchars($data['book_date']) ?></li>
  <li><strong>Event Time:</strong> <?= htmlspecialchars($data['start_time']) ?></li>
  <li><strong>Status:</strong> <?= htmlspecialchars(ucfirst($data['status'] ?? 'Pending')) ?></li>
  <li><strong>Submitted At:</strong> <?= htmlspecialchars($data['submitted_at']) ?></li>
  <li><strong>Decline Reason:</strong> <?= htmlspecialchars($data['decline_reason']) ?></li>

  <?php
  renderFileField('Marriage License', $data['marriage_license']);
  renderFileField('Application Form', $data['application_form']);
  renderFileField('Birth Certificates', $data['birth_certificates']);
  renderFileField('Certificate of No Marriage', $data['certificate_of_no_marriage']);
  renderFileField('Community Tax Certificate', $data['community_tax_certificate']);
  renderFileField('Parental Consent Advice Groom', $data['parental_consent_advice_groom']);
  renderFileField('Parental Consent Advice Bride', $data['parental_consent_advice_bride']);
  renderFileField('Valid IDs Groom', $data['valid_ids_groom']);
  renderFileField('Valid IDs Bride', $data['valid_ids_bride']);
  renderFileField('Barangay Certificate', $data['barangay_certificate']);
  renderFileField('Canonical Interview', $data['canonical_interview']);
  ?>
</ul>

<!-- Add Date Picker and Slot Selection -->
<form id="wedding-form" method="POST" action="update_wedding_application.php">
  <label for="book_date">Select Date:</label>
  <input type="text" id="book_date" name="book_date" value="<?= htmlspecialchars($data['book_date']) ?>" required>
  
  <label for="deceased_name">Deceased Name:</label>
  <input type="text" id="deceased_name" name="deceased_name" value="<?= htmlspecialchars($data['deceased_name']) ?>" required>

  <label for="deceased_name">Deceased Name:</label>
  <input type="text" id="deceased_name" name="deceased_name" value="<?= htmlspecialchars($data['deceased_name']) ?>" required>

  <label for="deceased_name">Deceased Name:</label>
  <input type="text" id="deceased_name" name="deceased_name" value="<?= htmlspecialchars($data['deceased_name']) ?>" required>

  <label for="deceased_name">Deceased Name:</label>
  <input type="text" id="deceased_name" name="deceased_name" value="<?= htmlspecialchars($data['deceased_name']) ?>" required>

  <label for="deceased_name">Deceased Name:</label>
  <input type="text" id="deceased_name" name="deceased_name" value="<?= htmlspecialchars($data['deceased_name']) ?>" required>
  

  <label for="deceased_name">Deceased Name:</label>
  <input type="text" id="deceased_name" name="deceased_name" value="<?= htmlspecialchars($data['deceased_name']) ?>" required>


  <label for="deceased_name">Deceased Name:</label>
  <input type="text" id="deceased_name" name="deceased_name" value="<?= htmlspecialchars($data['deceased_name']) ?>" required>


  <label for="deceased_name">Deceased Name:</label>
  <input type="text" id="deceased_name" name="deceased_name" value="<?= htmlspecialchars($data['deceased_name']) ?>" required>

  <label for="deceased_name">Deceased Name:</label>
  <input type="text" id="deceased_name" name="deceased_name" value="<?= htmlspecialchars($data['deceased_name']) ?>" required>

  <label for="deceased_name">Deceased Name:</label>
  <input type="text" id="deceased_name" name="deceased_name" value="<?= htmlspecialchars($data['deceased_name']) ?>" required>

  <label for="deceased_name">Deceased Name:</label>
  <input type="text" id="deceased_name" name="deceased_name" value="<?= htmlspecialchars($data['deceased_name']) ?>" required>

  <label for="deceased_name">Deceased Name:</label>
  <input type="text" id="deceased_name" name="deceased_name" value="<?= htmlspecialchars($data['deceased_name']) ?>" required>

  <label for="deceased_name">Deceased Name:</label>
  <input type="text" id="deceased_name" name="deceased_name" value="<?= htmlspecialchars($data['deceased_name']) ?>" required>


  <label for="start_time">Select Time:</label>
  <select id="start_time" name="start_time">
    <option value="09:00:00" <?= $data['start_time'] == '09:00:00' ? 'selected' : '' ?>>09:00 AM</option>
    <option value="13:00:00" <?= $data['start_time'] == '13:00:00' ? 'selected' : '' ?>>01:00 PM</option>
  </select>
  <input type="submit" value="Update Wedding Application">
</form>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var bookedSlots = <?= $bookedSlotsJson ?>;
    var bookDateInput = document.getElementById("book_date");
    var timeSelect = document.getElementById("start_time");

    // Function to update available times
    function updateAvailableTimes(selectedDate) {
        var selectedDateStr = selectedDate.toISOString().split('T')[0];
        var approved = bookedSlots[selectedDateStr] || [];
        var options = timeSelect.querySelectorAll("option");

        options.forEach(function (option) {
            option.disabled = approved.includes(option.value);
        });
    }

    flatpickr(bookDateInput, {
        dateFormat: "Y-m-d",
        minDate: "today",
        onChange: function (selectedDates) {
            if (selectedDates.length > 0) {
                updateAvailableTimes(selectedDates[0]);
            }
        }
    });
});
</script>

</body>
</html>
