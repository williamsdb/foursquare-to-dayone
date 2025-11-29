<a name="readme-top"></a>


<!-- PROJECT LOGO -->
<br />
<div align="center">

<h3 align="center">Foursquare to Day One</h3>


  <p align="center">
    A simple script to allow you to export all your Foursquare check-ins to a Day One Journal
    <br />
  </p>
</div>



<!-- TABLE OF CONTENTS -->
<details>
  <summary>Table of Contents</summary>
  <ol>
    <li>
      <a href="#about-the-project">About The Project</a>
      <ul>
        <li><a href="#built-with">Built With</a></li>
      </ul>
    </li>
    <li>
      <a href="#getting-started">Getting Started</a>
      <ul>
        <li><a href="#prerequisites">Prerequisites</a></li>
        <li><a href="#installation">Installation</a></li>
      </ul>
    </li>
    <li><a href="#usage">Usage</a></li>
    <li><a href="#roadmap">Roadmap</a></li>
    <li><a href="#contributing">Contributing</a></li>
    <li><a href="#license">License</a></li>
    <li><a href="#contact">Contact</a></li>
    <li><a href="#acknowledgments">Acknowledgments</a></li>
  </ol>
</details>



<!-- ABOUT THE PROJECT -->
## About The Project

Following on from [last month's project](https://github.com/williamsdb/wordpress-to-dayone) to get [WordPress](https://wordpress.org/) blog posts into [Day One](https://dayoneapp.com/) this time it's getting your [Foursquare](https://foursquare.com/)/[Swarm](https://www.swarmapp.com/) check-ins into a Day One journal.

This actually requires more setup than piping WordPress blog posts to Day One, as it needs both Foursquare API access and Mapbox too (if you want to include maps).

This README describes how to get the script up and running, but if you want to read about the background to the project and what it took to get it working, you can read more about that [here](https://www.spokenlikeageek.com/2025/12/01/exporting-foursquare-check-ins-to-day-one/).


![](https://www.spokenlikeageek.com/wp-content/uploads/2025/11/SCR-20251129-jafx-scaled.jpeg)

<p align="right">(<a href="#readme-top">back to top</a>)</p>

### Built With

* [PHP](https://php.net)
* [Day One CLI](https://dayoneapp.com/guides/tips-and-tutorials/command-line-interface-cli/)
* [Foursquare API](https://foursquare.com/developers/home)
* [Mapbox API](https://www.mapbox.com/)

<p align="right">(<a href="#readme-top">back to top</a>)</p>

<!-- GETTING STARTED -->
## Getting Started

Getting the script up and running is very straightforward:

1. download the code
2. setup the configuration params
3. run php oauth.php
4. copy the access code to the config file
5. run php f2do.php

You can read more about how this all works in [this blog post](https://www.spokenlikeageek.com/2025/12/01/exporting-foursquare-check-ins-to-day-one/) and check the details installation instructions below.

### Prerequisites

Requirements are very simple; it requires the following:

1. PHP (I tested on v8.4.7)
2. Foursquare/Swarm account with check-ins
3. access to the [Foursquare API](https://foursquare.com/developers/home)
4. access to the [Mapbox API](https://www.mapbox.com/) (if you want to include maps in each post)
3. a Day One account with the CLI installed (see [this post](https://dayoneapp.com/guides/tips-and-tutorials/command-line-interface-cli/) for how to do that).

IMPORTANT! This is Mac only, sorry!

### Installation

To get setup follow the instructions below:

1. Clone the repo:
   ```sh
   git clone https://github.com/williamsdb/foursquare-to-dayone.git
   ```
3. open ```src/config.php``` and add your Foursquare client key, secret and redirect URI
4. run ```php oauth.php``` from the command line
5. copy the Access Token and add it to the src/config.php file
6. if you want a map to be included in the Day One entry paste your Mapbox token in ```src/config.php``` and create the folder ```src/maps```. If not set INCLUDE_MAPS to FALSE
7. set your Day One journal name, in which the entries will be created in
4. follow the instructions below to run.

<p align="right">(<a href="#readme-top">back to top</a>)</p>


<!-- USAGE EXAMPLES -->
## Usage

From the command line, run the script: ```php f2do.php``` 

If you have logging turned on, you will see a screen similar to the following as entries are created.

![](https://www.spokenlikeageek.com/wp-content/uploads/2025/11/SCR-20251128-nrql.png)

If you stop the process at any time (or it fails), then when you run the script again it will restart where it left off. If you don't want this to happen delete the ```progress.json``` file in the same folder as the script.


###IMPORTANT!

The progress shown in the terminal will be ahead of Day One, so don't panic if you can't see the entries. It may take as much as a minute or more for Day One to catch up. It will also use quite a lot of CPU and memory while it is running.

_For more information, please refer to the [this blog post](https://www.spokenlikeageek.com/2025/12/01/exporting-foursquare-check-ins-to-day-one/)_

<p align="right">(<a href="#readme-top">back to top</a>)</p>



<!-- ROADMAP -->
## Known Issues

See the [open issues](https://github.com/williamsdb/foursquare-to-dayone/issues) for a full list of proposed features (and known issues).

<p align="right">(<a href="#readme-top">back to top</a>)</p>



<!-- CONTRIBUTING -->
## Contributing

Contributions are what make the open source community such an amazing place to learn, inspire, and create. Any contributions you make are **greatly appreciated**.

If you have a suggestion that would make this better, please fork the repo and create a pull request. You can also simply open an issue with the tag "enhancement".
Don't forget to give the project a star! Thanks again!

1. Fork the Project
2. Create your Feature Branch (`git checkout -b feature/AmazingFeature`)
3. Commit your Changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the Branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

<p align="right">(<a href="#readme-top">back to top</a>)</p>



<!-- LICENSE -->
## License

Distributed under the GNU General Public License v3.0. See `LICENSE` for more information.

<p align="right">(<a href="#readme-top">back to top</a>)</p>



<!-- CONTACT -->
## Contact

X - [@spokenlikeageek](https://x.com/spokenlikeageek) 

Bluesky - [@spokenlikeageek.com](https://bsky.app/profile/spokenlikeageek.com)

Mastodon - [@spokenlikeageek](https://techhub.social/@spokenlikeageek)

Website - [https://spokenlikeageek.com](https://www.spokenlikeageek.com/tag/foursquare-to-dayone/)

Project link - [Github](https://github.com/williamsdb/foursquare-to-dayone)

<p align="right">(<a href="#readme-top">back to top</a>)</p>



<!-- ACKNOWLEDGMENTS -->
## Acknowledgments

* [Day One CLI](https://dayoneapp.com/guides/tips-and-tutorials/command-line-interface-cli/)

<p align="right">(<a href="#readme-top">back to top</a>)</p>



<!-- MARKDOWN LINKS & IMAGES -->
<!-- https://www.markdownguide.org/basic-syntax/#reference-style-links -->
[contributors-shield]: https://img.shields.io/github/contributors/github_username/repo_name.svg?style=for-the-badge
[contributors-url]: https://github.com/github_username/repo_name/graphs/contributors
[forks-shield]: https://img.shields.io/github/forks/github_username/repo_name.svg?style=for-the-badge
[forks-url]: https://github.com/github_username/repo_name/network/members
[stars-shield]: https://img.shields.io/github/stars/github_username/repo_name.svg?style=for-the-badge
[stars-url]: https://github.com/github_username/repo_name/stargazers
[issues-shield]: https://img.shields.io/github/issues/github_username/repo_name.svg?style=for-the-badge
[issues-url]: https://github.com/github_username/repo_name/issues
[license-shield]: https://img.shields.io/github/license/github_username/repo_name.svg?style=for-the-badge
[license-url]: https://github.com/github_username/repo_name/blob/master/LICENSE.txt
[linkedin-shield]: https://img.shields.io/badge/-LinkedIn-black.svg?style=for-the-badge&logo=linkedin&colorB=555
[linkedin-url]: https://linkedin.com/in/linkedin_username
[product-screenshot]: images/screenshot.png
[Next.js]: https://img.shields.io/badge/next.js-000000?style=for-the-badge&logo=nextdotjs&logoColor=white
[Next-url]: https://nextjs.org/
[React.js]: https://img.shields.io/badge/React-20232A?style=for-the-badge&logo=react&logoColor=61DAFB
[React-url]: https://reactjs.org/
[Vue.js]: https://img.shields.io/badge/Vue.js-35495E?style=for-the-badge&logo=vuedotjs&logoColor=4FC08D
[Vue-url]: https://vuejs.org/
[Angular.io]: https://img.shields.io/badge/Angular-DD0031?style=for-the-badge&logo=angular&logoColor=white
[Angular-url]: https://angular.io/
[Svelte.dev]: https://img.shields.io/badge/Svelte-4A4A55?style=for-the-badge&logo=svelte&logoColor=FF3E00
[Svelte-url]: https://svelte.dev/
[Laravel.com]: https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white
[Laravel-url]: https://laravel.com
[Bootstrap.com]: https://img.shields.io/badge/Bootstrap-563D7C?style=for-the-badge&logo=bootstrap&logoColor=white
[Bootstrap-url]: https://getbootstrap.com
[JQuery.com]: https://img.shields.io/badge/jQuery-0769AD?style=for-the-badge&logo=jquery&logoColor=white
[JQuery-url]: https://jquery.com 
