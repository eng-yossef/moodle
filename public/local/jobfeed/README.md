# Local Job Feed Plugin for Moodle

Integrates external job listings API into Moodle with a clean, responsive UI.

## Features

- ✅ Fetch jobs from configurable API endpoint
- ✅ Filter by skill via URL parameter (`?skill=java`)
- ✅ Responsive card-based UI using Moodle's Mustache templates
- ✅ Proper error handling and user feedback
- ✅ Configurable via Site administration > Plugins > Local plugins > Job Feed
- ✅ Follows Moodle coding standards and security best practices

## Installation

1. **Download/Clone the plugin** into your Moodle installation:
   ```bash
   cd /path/to/moodle/local
   git clone <repository-url> jobfeed
   # OR manually create the directory structure and upload files