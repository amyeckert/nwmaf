<?php

//ADD Edit profile TAB TO THE NAV MENU for the user who is logged in
function mepr_add_edit_profile_tab($user) {
    ?>
     <span class="mepr-nav-item">
        <a href="/member-directory" rel="noopener noreferrer">Member Directory Profile</a>
    </span>
     <span class="mepr-nav-item">
        <a href="/resources/empowerment/nwmaf-certified-instructors/" rel="noopener noreferrer">Certified Instructor Directory Profile</a>
    </span>
<?php
}

add_action('mepr_account_nav', 'mepr_add_edit_profile_tab');
?>
