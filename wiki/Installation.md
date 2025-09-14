# Installation Guide

This guide provides detailed instructions on how to install the Advanced Log Manager plugin on your WordPress website. You can choose between two primary methods: installing directly from the WordPress admin dashboard or manually via FTP. Both methods are straightforward, but it's important to follow the steps carefully.

## Prerequisites

Before you begin the installation process, please ensure the following:

*   **WordPress Installation:** You have a working WordPress installation (version 5.0 or higher is recommended).
*   **Administrator Access:** You have administrator privileges to your WordPress dashboard.
*   **FTP Client (for Method 2):** If you plan to install via FTP, you will need an FTP client (e.g., FileZilla, Cyberduck) and your FTP credentials (hostname, username, password).
*   **Plugin File:** You have the `advanced-log-manager.zip` file downloaded from the official source.

## Method 1: Install from the WordPress Admin Dashboard (Recommended)

This is the easiest and most recommended method for installing WordPress plugins.

1.  **Log in to your WordPress Admin Dashboard:** Open your web browser and navigate to your WordPress admin area (e.g., `yourdomain.com/wp-admin`). Enter your username and password to log in.

2.  **Navigate to the Plugins Page:** In the left-hand sidebar of your dashboard, hover over **"Plugins"** and then click on **"Add New."** This will take you to the "Add Plugins" screen.

3.  **Upload the Plugin:** At the top of the "Add Plugins" page, you will see a button labeled **"Upload Plugin."** Click this button.

4.  **Choose the Plugin File:** A file upload form will appear. Click the **"Choose File"** button. A file browser window will open. Navigate to the location where you saved the `advanced-log-manager.zip` file on your computer, select it, and then click **"Open"** (or equivalent).

5.  **Install the Plugin:** After selecting the file, click the **"Install Now"** button. WordPress will now upload the plugin file from your computer to your website and install it. You will see a progress indicator and messages about the installation status.

6.  **Activate the Plugin:** Once the installation is complete, WordPress will display a success message: "Plugin installed successfully." Below this message, you will find a button labeled **"Activate Plugin."** Click this button to enable the Advanced Log Manager on your site.

7.  **Verification:** After activation, you should be redirected to the main "Plugins" page, and you will see "Advanced Log Manager" listed among your active plugins. A new menu item for Advanced Log Manager should also appear in your WordPress admin sidebar.

## Method 2: Install via FTP (Manual Installation)

This method is useful if you cannot install plugins directly from the WordPress dashboard or prefer a manual approach.

1.  **Extract the Plugin Files:** Locate the `advanced-log-manager.zip` file on your computer. Right-click on it and select "Extract All" or "Unzip." This will create a new folder named `advanced-log-manager` containing all the plugin files.

2.  **Connect to Your Server via FTP:** Open your preferred FTP client (e.g., FileZilla). Enter your FTP hostname, username, and password to connect to your web server. If you don't have these credentials, contact your hosting provider.

3.  **Navigate to the Plugins Directory:** Once connected, navigate to your WordPress installation directory. Inside, you will find the `wp-content` folder. Open `wp-content`, and then open the `plugins` folder. The full path will typically look something like `/public_html/wp-content/plugins/` or `/www/wp-content/plugins/`.

4.  **Upload the Plugin Folder:** Drag and drop the entire `advanced-log-manager` folder (the one you extracted in step 1) from your computer's local site panel to the `wp-content/plugins` directory on your remote site panel in the FTP client. Ensure all files and subfolders are uploaded correctly.

5.  **Log in to your WordPress Admin Dashboard:** After the upload is complete, open your web browser and log in to your WordPress admin area (e.g., `yourdomain.com/wp-admin`).

6.  **Activate the Plugin:** In the left-hand sidebar, hover over **"Plugins"** and click on **"Installed Plugins."** On the "Plugins" page, locate "Advanced Log Manager" in the list. Click the **"Activate"** link beneath its name.

7.  **Verification:** Similar to Method 1, after activation, you should see "Advanced Log Manager" listed as an active plugin, and its menu item should appear in your WordPress admin sidebar.

## Post-Installation Steps

After successfully installing and activating the Advanced Log Manager plugin, it is recommended to:

*   **Review Settings:** Navigate to the Advanced Log Manager settings page in your WordPress dashboard to configure the plugin according to your needs.
*   **Enable Debugging (if necessary):** If you intend to use the Log Manager for debugging, ensure that WordPress debugging is enabled in your `wp-config.php` file (refer to the `Configuration.md` guide for detailed instructions).
*   **Explore Features:** Familiarize yourself with the various features like Log Manager, Query Monitor, SMTP Logs, etc., to make the most out of the plugin.