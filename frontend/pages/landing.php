<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="frontend/assets/css/landingpage.css">
    <title>CoolFreeze</title>
</head>
<body>
    <div class="navmenu">
        <ul>
            <li class="logo"><img src="frontend/assets/img/coolfreeze_horizontal_logo.svg" width="250px" height="50px" alt="profiles"></li>
            <a href="<?= BASE_URL ?>?page=login" ><li class="primarybtn">Register<img src="frontend/assets/img/right.svg" alt="profiles"></li></a>
        </ul>
    </div>

    <section id="hero">
        <div class="herowrap">
            <div class="herocontainer">
                <div class="herosection">
                    <div class="ratings">
                        <img src="frontend/assets/img/Heads.svg" alt="profiles">
                        <div class="rates">
                            <div class="stars">
                                <p>Rating</p>
                                <div class="rated">
                                    <img src="frontend/assets/img/star.svg" alt="stars">
                                    <p>4.9</p>
                                </div>
                            </div>
                            <p>Over a thousand users</p>
                        </div>
                    </div>
                    <div class="herotextcontainer">
                        <p class="maintext">Reliable Air Conditioning Services for Homes and Businesses</p>
                        <p class="subtext">Buy, sell, repair, clean, maintain, and find parts for your air-conditioning needs — all in one place.</p>
                    </div>
                </div>
                <div class="btncontainer">
                    <a href="<?= BASE_URL ?>?page=login"><div class="primarybtn">Book a Service<img src="frontend/assets/img/right.svg" alt="profiles"></div></a>
                    <a href="#whychooseus"><div class="secondarybtn">Explore<img src="frontend/assets/img/down.svg" alt="profiles"></div></a>
                </div>
            </div>

            <div class="heroimage">
                <img src="frontend/assets/img/Mokker.svg" alt="Technician servicing an air conditioning unit">
            </div>
        </div>

        <div class="herowave" aria-hidden="true">
            <svg viewBox="0 0 1440 120" preserveAspectRatio="none">
                <path d="M0,64 C240,120 480,0 720,32 C960,64 1200,112 1440,48 L1440,120 L0,120 Z"></path>
            </svg>
        </div>

        <div class="serviceautoscroll">
            <div class="serviceautoscrollcontent">
                <div class="serviceautoscroll__group">
                    <img src="frontend/assets/img/4star.svg" alt="">
                    <p>AC Repair</p>
                    <img src="frontend/assets/img/4star.svg" alt="">
                    <p>AC Installation</p>
                    <img src="frontend/assets/img/4star.svg" alt="">
                    <p>AC Cleaning</p>
                    <img src="frontend/assets/img/4star.svg" alt="">
                    <p>AC Maintenance</p>
                    <img src="frontend/assets/img/4star.svg" alt="">
                    <p>Parts Replacement</p>
                </div>
                <div class="serviceautoscroll__group" aria-hidden="true">
                    <img src="frontend/assets/img/4star.svg" alt="">
                    <p>AC Repair</p>
                    <img src="frontend/assets/img/4star.svg" alt="">
                    <p>AC Installation</p>
                    <img src="frontend/assets/img/4star.svg" alt="">
                    <p>AC Cleaning</p>
                    <img src="frontend/assets/img/4star.svg" alt="">
                    <p>AC Maintenance</p>
                    <img src="frontend/assets/img/4star.svg" alt="">
                    <p>Parts Replacement</p>
                </div>
                <div class="serviceautoscroll__group" aria-hidden="true">
                    <img src="frontend/assets/img/4star.svg" alt="">
                    <p>AC Repair</p>
                    <img src="frontend/assets/img/4star.svg" alt="">
                    <p>AC Installation</p>
                    <img src="frontend/assets/img/4star.svg" alt="">
                    <p>AC Cleaning</p>
                    <img src="frontend/assets/img/4star.svg" alt="">
                    <p>AC Maintenance</p>
                    <img src="frontend/assets/img/4star.svg" alt="">
                    <p>Parts Replacement</p>
                </div>
            </div>
        </div>
    </section>



    <!--why coolfreeze dito, nakaname as persuade-->
    <section id="whychooseus">
        <div class="headingtext">
            <div class="question">
                <span>-</span>
                <p>Why choose CoolFreeze?</p>
                <span>-</span>
            </div>
            <div class="persuade">
                <p class="mainpersuade">Your Comfort is our <span>top Priority</span></p>
                <p>We provide reliable, professional, and affordable aircon services to keep your home and business cool, clean, and comfortable all year around</p>
            </div>
        </div>
        <div class="persuadecards">
            <div class="persuadecard">
                <div class="persuadecard__icon"><i class="bi bi-shield-check"></i></div>
                <p class="persuadecardtitle">Safe & Healthy Home</p>
                <p>We remove dirt, dust, and harmful germs to keep your indoor air fresh and clean</p>
            </div>
            <div class="persuadecard">
                <div class="persuadecard__icon"><i class="bi bi-people-fill"></i></div>
                <p class="persuadecardtitle">Trained & Trusted Team</p>
                <p>Skilled professionals who treat your home with care and respect</p>
            </div>
        </div>
        <div class="persuadeimg"><img src="frontend/assets/img/Him.png" alt=""></div>
        <div class="persuadecards">
            <div class="persuadecard">
                <div class="persuadecard__icon"><i class="bi bi-calendar2-check"></i></div>
                <p class="persuadecardtitle">Easy Online Booking</p>
                <p>Choose a schedule that works for you. We make it simple and convenient</p>
            </div>
            <div class="persuadecard">
                <div class="persuadecard__icon"><i class="bi bi-building"></i></div>
                <p class="persuadecardtitle">Homes & Businesses</p>
                <p>Suitable for residential and commercial needs</p>
            </div>
        </div>
    </section>

    <section id="whatweoffer">
        <div class="headingtext">
            <div class="question">
                <span>-</span>
                <p>What we offer?</p>
                <span>-</span>
            </div>
            <div class="persuade">
                <p class="mainpersuade">Complete Air Conditioning Solutions for <span>Your Comfort.</span></p>
                <p>From cleaning and maintenance to repairs and installation, CoolFreeze provides reliable air conditioning services to keep your home or business cool, comfortable, and running smoothly.</p>
            </div>
        </div>

        <div class="services-grid">

            <article class="service-card">
                <div class="service-card__icon"><i class="bi bi-cart3"></i></div>
                <div class="service-card__body">
                    <h3>AC<br>Installation</h3>
                    <p>Expert installation of new air conditioning units for efficient and reliable cooling.</p>
                    <!-- Kailangan palitan: loggedin->homepage & new->loginPage  -->
                    <a href="<?= BASE_URL ?>?page=login" class="service-card__link">Learn more <i class="bi bi-arrow-right"></i></a>
                </div>
            </article>

            <article class="service-card">
                <div class="service-card__icon"><i class="bi bi-wrench-adjustable"></i></div>
                <div class="service-card__body">
                    <h3>AC<br>Repair</h3>
                    <p>Fast and reliable repairs to restore your AC’s cooling performance and comfort.</p>
                    <!-- Kailangan palitan: loggedin->homepage & new->loginPage  -->
                    <a href="<?= BASE_URL ?>?page=login" class="service-card__link">Learn more <i class="bi bi-arrow-right"></i></a>
                </div>
            </article>

            <article class="service-card">
                <div class="service-card__icon"><i class="bi bi-droplet"></i></div>
                <div class="service-card__body">
                    <h3>AC<br>Cleaning</h3>
                    <p>Thorough AC cleaning to improve airflow, cooling efficiency, and indoor air quality.</p>
                    <!-- Kailangan palitan: loggedin->homepage & new->loginPage  -->
                    <a href="<?= BASE_URL ?>?page=login" class="service-card__link">Learn more <i class="bi bi-arrow-right"></i></a>
                </div>
            </article>

            <article class="service-card service-card--wide">
                <div class="service-card__icon"><i class="bi bi-gear"></i></div>
                <div class="service-card__body">
                    <h3>Parts<br>Replacement</h3>
                    <p>Quality replacement of worn or damaged AC parts to keep your system running smoothly.</p>
                    <!-- Kailangan palitan: loggedin->homepage & new->loginPage  -->
                    <a href="<?= BASE_URL ?>?page=login" class="service-card__link">Learn more <i class="bi bi-arrow-right"></i></a>
                </div>
            </article>

            <article class="service-card service-card--wide">
                <div class="service-card__icon"><i class="bi bi-thermometer-snow"></i></div>
                <div class="service-card__body">
                    <h3>AC<br>Maintenance</h3>
                    <p>Regular AC maintenance to prevent problems, improve efficiency, and extend system life.</p>
                    <!-- Kailangan palitan: loggedin->homepage & new->loginPage  -->
                    <a href="<?= BASE_URL ?>?page=login" class="service-card__link">Learn more <i class="bi bi-arrow-right"></i></a>
                </div>
            </article>

        </div>
    </section>

    <section id="howwework">
        <div class="headingtext">
            <div class="question">
                <span>-</span>
                <p>How we work</p>
                <span>-</span>
            </div>
            <div class="persuade">
                <p class="mainpersuade">Simple Steps to a Cooler, More Comfortable Space.</p>
                <p>From requesting a service to getting the job done, we make the process simple, convenient, and hassle-free.</p>
            </div>
        </div>

        <div class="process-grid">

            <article class="process-card">
                <span class="process-card__badge">1</span>
                <img class="process-card__img" src="frontend/assets/img/process.png" alt="" loading="lazy">
                <h3>Choose a Service</h3>
                <p>Select the air conditioning service you need.</p>
            </article>

            <article class="process-card">
                <span class="process-card__badge">2</span>
                <img class="process-card__img" src="frontend/assets/img/process2.png" alt="" loading="lazy">
                <h3>Submit Your Request</h3>
                <p>Provide your preferred schedule, location, and service details.</p>
            </article>

            <article class="process-card">
                <span class="process-card__badge">3</span>
                <img class="process-card__img" src="frontend/assets/img/process3.png" alt="" loading="lazy">
                <h3>We Review &amp; Confirm</h3>
                <p>Our team reviews your request and assigns the appropriate technicians.</p>
            </article>

            <article class="process-card process-card--offset">
                <span class="process-card__badge">4</span>
                <img class="process-card__img" src="frontend/assets/img/process4.png" alt="" loading="lazy">
                <h3>We Get the Job Done</h3>
                <p>Our team arrives at your location and completes the service.</p>
            </article>

            <article class="process-card process-card--offset">
                <span class="process-card__badge">5</span>
                <img class="process-card__img" src="frontend/assets/img/process5.png" alt="" loading="lazy">
                <h3>Service Completed</h3>
                <p>The service is completed and your request is updated.</p>
            </article>

        </div>
    </section>

    <section id="testimonials">
        <div class="herowave herowave--flip" aria-hidden="true">
            <svg viewBox="0 0 1440 120" preserveAspectRatio="none">
                <path d="M0,64 C240,120 480,0 720,32 C960,64 1200,112 1440,48 L1440,120 L0,120 Z"></path>
            </svg>
        </div>

        <div class="headingtext">
            <div class="question">
                <span>-</span>
                <p>Testimonials</p>
                <span>-</span>
            </div>
            <div class="persuade">
                <p class="mainpersuade">Reliable Service, Trusted by Our Customers</p>
                <p>See what homeowners and businesses are saying about their experience with CoolFreeze</p>
            </div>
        </div>

        <div class="testimonial-grid">

            <article class="testimonial-card">
                <div class="testimonial-card__stars">
                    <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                </div>
                <p class="testimonial-card__quote">Our AC hasn't run this smoothly in years. The technician was punctual, professional, and explained everything clearly.</p>
                <p class="testimonial-card__name">Marco D.</p>
                <p class="testimonial-card__role">Homeowner</p>
            </article>

            <article class="testimonial-card">
                <div class="testimonial-card__stars">
                    <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                </div>
                <p class="testimonial-card__quote">Booking was so easy and the team showed up exactly on time. Highly recommend for any business needing reliable service.</p>
                <p class="testimonial-card__name">Angela R.</p>
                <p class="testimonial-card__role">Business Owner</p>
            </article>

            <article class="testimonial-card">
                <div class="testimonial-card__stars">
                    <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                </div>
                <p class="testimonial-card__quote">Fast, affordable, and thorough. They found and fixed a leak our last provider missed completely.</p>
                <p class="testimonial-card__name">Jerome T.</p>
                <p class="testimonial-card__role">Homeowner</p>
            </article>

        </div>
    </section>

    <section id="getstarted">
        <div class="getstarted__inner">
            <div class="getstarted__text">
                <div class="question">
                    <span>-</span>
                    <p>Ready to book?</p>
                    <span>-</span>
                </div>
                <p class="mainpersuade">Get Started with CoolFreeze Today</p>
                <p class="getstarted__subtext">Book a service in just a few minutes and let our team take care of the rest.</p>
                <a href="<?= BASE_URL ?>?page=login"><div class="primarybtn">Book a Service<img src="frontend/assets/img/right.svg" alt="profiles"></div></a>
            </div>
            
            <div class="getstarted__mockup">
                <img src="frontend/assets/img/mockup.png" alt="CoolFreeze reports dashboard preview">
            </div>
        </div>
    </section>

    <footer id="sitefooter">
        <div class="footer__top">
            <div class="footer__brand">
                <img src="frontend/assets/img/CoolFreeze_horizontal_logo.svg" alt="CoolFreeze">
                <p>Reliable air conditioning services for homes and businesses — installation, repair, cleaning, maintenance, and parts, all in one place.</p>
                <div class="footer__social">
                    <a href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="#" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="#" aria-label="X"><i class="bi bi-twitter-x"></i></a>
                </div>
            </div>

            <div class="footer__col">
                <p class="footer__heading">Services</p>
                <ul>
                    <li><a href="#whatweoffer">AC Installation</a></li>
                    <li><a href="#whatweoffer">AC Repair</a></li>
                    <li><a href="#whatweoffer">AC Cleaning</a></li>
                    <li><a href="#whatweoffer">AC Maintenance</a></li>
                    <li><a href="#whatweoffer">Parts Replacement</a></li>
                </ul>
            </div>

            <div class="footer__col">
                <p class="footer__heading">Sections</p>
                <ul>
                    <li><a href="#whychooseus">Why Choose Us</a></li>
                    <li><a href="#howwework">How We Work</a></li>
                    <li><a href="#testimonials">Testimonials</a></li>
                    <li><a href="login.php">Book a Service</a></li>
                </ul>
            </div>

            <div class="footer__col">
                <p class="footer__heading">Get In Touch</p>
                <ul>
                    <li><i class="bi bi-telephone"></i> (02) 8123 4567</li>
                    <li><i class="bi bi-envelope"></i> hello@coolfreeze.com</li>
                    <li><i class="bi bi-geo-alt"></i> Metro Manila, Philippines</li>
                </ul>
            </div>
        </div>

        <div class="footer__bottom">
            <p>&copy; 2026 CoolFreeze. All rights reserved.</p>
            <div class="footer__legal">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
            </div>
        </div>
    </footer>

</body>
</html>