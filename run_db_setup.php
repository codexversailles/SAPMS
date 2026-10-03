<?php
// This script is a web-accessible way to run the database setup

echo '<h1>Setting up Parent Teacher Messages</h1>';

// Include the setup script
require_once 'setup_parent_messages.php';

echo '<h2>Setup Completed</h2>';
echo '<p>You can now close this page and return to the parent dashboard.</p>';
echo '<p><a href="parent-dashboard.html">Go to Parent Dashboard</a></p>'; 