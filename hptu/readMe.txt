# COUNTY COLOR: MOMBASA -

#011f88; DARK-BLUE
#E6E6E6; GREY

if showing emty
echo "<span class='location-badge'>" . htmlspecialchars($_SESSION['countyname']) . "</span>";

TO THIS

echo "<span class='location-badge'>" . (empty($_SESSION['countyname']) ? "Unknown" : htmlspecialchars($_SESSION['countyname'])) . "</span>";