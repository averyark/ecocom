<?php
/**
 * Database Setup Script for EcoCom Project
 *
 * This script creates the database and all required tables for the EcoCom project.
 * It uses the SQL file located at ../../database/ecocom_db.sql to set up the database structure.
 * Run this script once to set up your database structure.
 */

// Database connection parameters
$host = 'localhost';
$user = 'root';
$pass = ''; // Update with your MySQL password if needed
$charset = 'utf8mb4';
$sqlFilePath = __DIR__ . '/../sql/ecocom_db.sql';

// Connect to MySQL server without selecting a database
$conn = new mysqli($host, $user, $pass);

// Check connection
if ($conn->connect_error) {
    die("Database setup failed: Connection failed: {$conn->connect_error}");
}

// echo "Connected to MySQL server successfully.<br>";

// Check if SQL file exists
if (!file_exists($sqlFilePath)) {
    die("SQL file not found at: $sqlFilePath");
}

// Read SQL file
$sql = file_get_contents($sqlFilePath);
if ($sql === false) {
    die("Failed to read SQL file: $sqlFilePath");
}

// echo "SQL file loaded successfully.<br>";

// Split SQL file into individual statements
$statements = array_filter(
    array_map(
        'trim',
        explode(';', $sql)
    ),
    fn($statement) => !empty($statement)
);

// Execute each statement
foreach ($statements as $statement) {
    // Check if the statement is trying to create a database
    if (preg_match('/CREATE\s+DATABASE/i', $statement)) {
        // Check if the database already exists
        $dbName = preg_replace('/CREATE\s+DATABASE\s+IF\s+NOT\s+EXISTS\s+/i', '', $statement);
        $dbName = trim($dbName, '`');
        $result = $conn->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$dbName'");
        if ($result->num_rows > 0) {
            // echo "Database '$dbName' already exists. Skipping creation.<br>";
            return;
        }
    }

    if ($conn->query($statement) === TRUE) {
        // Extract table name for logging (simple regex to get table name after CREATE TABLE IF NOT EXISTS)
        if (preg_match('/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+(\w+)/i', $statement, $matches)) {
            echo "Table '{$matches[1]}' created or already exists.<br>";
        } elseif (preg_match('/CREATE\s+DATABASE/i', $statement)) {
            echo "Database created or already exists.<br>";
        } elseif (preg_match('/USE\s+(\w+)/i', $statement, $matches)) {
            echo "Using database '{$matches[1]}'.<br>";
        } elseif (preg_match('/CREATE\s+INDEX/i', $statement)) {
            echo "Index created successfully.<br>";
        }
    } else {
        echo "Error executing statement: {$conn->error}<br>";
        echo "Statement: {$statement}<br>";
    }
}

echo "<br><strong>Database setup completed successfully!</strong>";

// Create admin user
$adminUsername = 'admin';
$adminPassword = password_hash('admin', PASSWORD_DEFAULT);
$adminEmail = 'admin@admin.com';
$adminBirthdate = '1990-01-01';
$adminIsAdmin = true;

$stmt = $conn->prepare("INSERT INTO users (email, username, password, birthdate, is_admin) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("sssss", $adminEmail, $adminUsername, $adminPassword, $adminBirthdate, $adminIsAdmin);
$stmt->execute();
echo "Admin user created successfully.<br>";

// Create 4 random gardens
$gardenNames = ['Eco Garden', 'Green Oasis', 'Urban Jungle', 'Harmony Park'];
$gardenDescriptions = [
    'A sustainable garden focused on eco-friendly practices.',
    'An oasis of greenery in the heart of the city.',
    'A wild and diverse urban garden ecosystem.',
    'A harmonious blend of native plants and community spaces.'
];
$gardenAddress = [
    '23 Jalan Bukit Bintang, 50200 Kuala Lumpur, Selangor, Malaysia',
    '17 Jalan Sultan Ismail, 11600 George Town, Penang, Malaysia',
    '4 Jalan Tebrau, 80100 Johor Bahru, Johor, Malaysia',
    '55 Jalan Gopeng, 31400 Ipoh, Perak, Malaysia'
];
$gardenStartDates = [
    '2025-01-01 09:00:00',
    '2025-02-15 10:00:00',
    '2025-03-01 08:30:00',
    '2025-04-15 11:00:00'
];
$gardenEndDates = [
    '2026-04-30 18:00:00',
    '2026-05-15 17:00:00',
    '2026-06-30 16:30:00',
    '2026-07-15 19:00:00'
];
$gardenRecurringDays = ['Monday', 'Wednesday', 'Friday', 'Saturday'];
$gardenRecurringStartTimes = ['09:00:00', '10:00:00', '08:30:00', '11:00:00'];
$gardenRecurringEndTimes = ['12:00:00', '13:00:00', '11:30:00', '14:00:00'];

for ($i = 0; $i < 4; $i++) {
    $stmt = $conn->prepare("INSERT INTO gardens (title, description, address, user_id, start_date, end_date, recurring_day, recurring_start_time, recurring_end_time) VALUES (?,?,?,1,?,?,?,?,?)");
    $stmt->bind_param("ssssssss", $gardenNames[$i], $gardenDescriptions[$i], $gardenAddress[$i], $gardenStartDates[$i], $gardenEndDates[$i], $gardenRecurringDays[$i], $gardenRecurringStartTimes[$i], $gardenRecurringEndTimes[$i]);
    $stmt->execute();
    echo "Garden '{$gardenNames[$i]}' created successfully.<br>";
}


// Create 1 recycling event
$recyclingTitle = 'Community Recycling Day';
$recyclingDescription = 'Join us for a day of recycling and environmental awareness.';
$recyclingLocation = 'Taman Tugu, Jalan Parlimen, 50480 Kuala Lumpur, Malaysia';
$recyclingItemsToRecycle = 'Paper, Plastic, Electronics, Glass';
$recyclingContact = 'recycling@ecocom.org';
$recyclingDate = '2025-05-22 09:00:00';
$recyclingEndDate = '2025-05-22 12:00:00';

$stmt = $conn->prepare("INSERT INTO recycling (title, description, location, item_to_recycle, contact, event_date, event_end_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("sssssss", $recyclingTitle, $recyclingDescription, $recyclingLocation, $recyclingItemsToRecycle, $recyclingContact, $recyclingDate, $recyclingEndDate);
$stmt->execute();
echo "Recycling event '{$recyclingTitle}' created successfully.<br>";


// Sample blog post data
$blog_posts = [
    [
        'title' => '7 tips to reduce energy consumption in your home or workplace',
        'content' => "Energy conservation is not just good for the environment; it's also great for your wallet. Here are seven practical tips to help you reduce energy consumption in your home or workplace:

1. Switch to LED lighting: LED bulbs use up to 75% less energy than traditional incandescent bulbs and last much longer.

2. Unplug electronics when not in use: Many devices continue to draw power even when turned off. This 'phantom energy' can account for up to 10% of your electricity bill.

3. Optimize your thermostat: Adjust your thermostat by just a few degrees (lower in winter, higher in summer) to save significant energy. Consider a programmable thermostat for automatic adjustments.

4. Seal air leaks: Check windows, doors, and ductwork for leaks and seal them with weatherstripping or caulk to prevent conditioned air from escaping.

5. Maintain your HVAC system: Regular maintenance of heating and cooling systems ensures they operate efficiently. Replace filters regularly.

6. Use energy-efficient appliances: When replacing appliances, look for ENERGY STAR certified models that use less electricity and water.

7. Harness natural light: Open curtains during the day to use natural sunlight instead of artificial lighting, but be mindful of heat gain in summer.",
        'author_id' => 1,
        'image_url' => '../../images/blogpic1.jpg',
    ],
    [
        'title' => 'Understanding recycling symbols: What those numbers really mean',
        'content' => "Recycling symbols can be confusing, but understanding them is crucial for proper waste management. Here's a breakdown of what those numbers inside the triangular recycling symbol actually mean:

#1 PET (Polyethylene Terephthalate): Commonly used for water bottles and food containers. Highly recyclable and often accepted in curbside programs.

#2 HDPE (High-Density Polyethylene): Found in milk jugs, detergent bottles, and toys. Also widely recyclable.

#3 PVC (Polyvinyl Chloride): Used in pipes, vinyl siding, and some food packaging. More difficult to recycle and may contain harmful chemicals.

#4 LDPE (Low-Density Polyethylene): Found in shopping bags, plastic wraps, and squeezable bottles. Increasingly recyclable but check local guidelines.

#5 PP (Polypropylene): Used in yogurt containers, medicine bottles, and bottle caps. Moderately recyclable.

#6 PS (Polystyrene/Styrofoam): Found in disposable cups, food containers, and packing materials. Difficult to recycle and rarely accepted in curbside programs.

#7 Other: A catch-all category for plastics that don't fit into the above categories, including bioplastics and multi-layer materials. Generally difficult to recycle.

Remember that recycling capabilities vary by location, so always check your local recycling guidelines to ensure you're recycling correctly.",
        'author_id' => 1,
        'image_url' => '../../images/recycling-symbols.jpg',
    ],
    [
        'title' => 'How to start your own community garden',
        'content' => "Community gardens are wonderful ways to bring people together while growing fresh, healthy food. Here's how to start one in your neighborhood:

1. Gather interested community members: Find like-minded individuals who share your passion for gardening and community building.

2. Find suitable land: Look for vacant lots, park spaces, or other areas that could be converted into a garden. Ensure it gets adequate sunlight and has access to water.

3. Secure permission: Contact the landowner or local government to obtain permission to use the space. You may need to create a formal agreement.

4. Test the soil: Before planting, test the soil for contaminants, especially in urban areas. Your local extension office can help with this.

5. Design your garden: Plan the layout, including individual plots, communal areas, pathways, and storage space for tools.

6. Establish rules and responsibilities: Create clear guidelines for membership, fees, maintenance responsibilities, and conflict resolution.

7. Build infrastructure: Install raised beds, irrigation systems, fencing, and tool storage as needed.

8. Start planting: Begin with easy-to-grow crops that match your climate and season.

9. Foster community: Organize regular workdays, workshops, and social events to build relationships among gardeners.

10. Maintain and grow: Continuously evaluate what's working and what isn't, and be open to evolving your garden over time.

Community gardens not only provide fresh produce but also create educational opportunities, improve neighborhood aesthetics, and strengthen community bonds.",
        'author_id' => 1,
        'image_url' => '../../images/community-garden.jpg',
    ],
];

// Insert sample blog posts
$insert_query = "INSERT INTO blog_posts (title, content, author_id, image_url) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($insert_query);

foreach ($blog_posts as $post) {
    $stmt->bind_param("ssis", $post['title'], $post['content'], $post['author_id'], $post['image_url']);
    if ($stmt->execute()) {
        echo "Added blog post: {$post['title']}<br>";
    } else {
        echo "Error adding blog post: {$post['title']} - {$conn->error}<br>";
    }
}

// Close connection
$conn->close();
