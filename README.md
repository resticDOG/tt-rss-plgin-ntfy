[中文](./README.CN.md)

## Features

- Supports sending RSS feed update notifications via Ntfy
- Can be used with TT-RSS filters to control notifications

## Installation

1. Clone this plugin to the TT-RSS plugin directory:

   ```bash
   cd /path/to/tt-rss/plugins
   git clone https://github.com/resticDOG/tt-rss-plugin-ntfy.git ntfy
   ```

2. Enable the plugin in the TT-RSS admin interface:
   - Go to Preferences -> Plugins
   - Find "Ntfy"
   - Click Enable

## Configuration

1. Configure the following parameters in the plugin settings:
   - Ntfy server address (e.g., <https://ntfy.sh>)
   - Notification topic (Topic)
   - Token

![image.png](https://img.linkzz.eu.org/main/images/2024/10/2db2ec2587ca9d38d05d581ab209c25b.png)

## Usage

### 1. Enable for a Feed

Enable the plugin on the feed edit page to push notifications when the feed
updates.

![image.png](https://img.linkzz.eu.org/main/images/2024/10/fda8b59913cbd5a6b3a8dc56f993bf1d.png)

### 2. Enable with Filter

Create a filter and select **Invoke plugin**: **Ntfy: Send Notification** in the
actions.

![image.png](https://img.linkzz.eu.org/main/images/2024/10/623b8bec5f8a53b86cb4de8cf63b762c.png)

### Troubleshooting

If you encounter issues, check:

1. Whether the PHP curl extension is installed
2. If the Ntfy server address is correct
3. TT-RSS logs for related error messages

### Contribution Guide

Pull Requests and Issues are welcome!

### Acknowledgments

- [Tiny Tiny RSS](https://tt-rss.org/)
- [Ntfy](https://ntfy.sh/)
- [mercury_fulltext](https://github.com/HenryQW/mercury_fulltext)

### Contact

For questions or suggestions, please contact via:

- Submit an [Issue](https://github.com/resticDOG/tt-rss-plugin-ntfy/issues)
- Pull Requests

---
