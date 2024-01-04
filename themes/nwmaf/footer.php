<?php do_action( 'main_bottom' ); ?>
</section> <!-- .main -->
<?php get_sidebar( 'primary' ); ?>
<?php do_action( 'after_main' ); ?>
</div><!-- .max-width -->
</main><!-- .primary-container -->

<footer id="site-footer" class="footer" role="contentinfo">
    <div class="row">
        <div class="col-12 footer__top">
            <figure class="footer__logo">
                <img src="/wp-content/uploads/2020/12/NWMAFLogo-450px-Color.png">
                <figcaption class="footer__text"><p><strong>The National Women&apos;s Martial Arts Federation (NWMAF)</strong></p><p>Building personal and collective strength, safety, and well-being through martial arts, self-defense, and healing arts, to empower women and others affected by gender-based discrimination.</p></figcaption>
            </figure>
        </div>
    </div>
    <div class="container">
        <div class="row footer__bottom pl-1 pr-1">
            <div class="col-12 col-md-8 col-lg-10 footer__menu">
                <a href="/ally-donation" class="btn cta" target="_blank">Donate</a>
                <?php wp_nav_menu( array( 'theme_location' => 'footer' ) ); ?>
            </div>
            <!-- <div class="col-12 col-md-4 col-lg-3 text-left footer__address">
                <h2 class="h3">Contact</h2> -->
                <!-- <address>
                    NWMAF<br>
                    304 S Jones Blvd, #2929<br>
                    Las Vegas, NV 89107<br><br>
                    <a href="mailto:secretary@nwmaf.org">secretary@nwmaf.org</a>
                </address> -->
            <!-- </div> -->
            <div class="col-12 col-md-4 col-lg-2 footer__social-links">
                <h2 class="h3">Follow Us</h2>
                <ul id="menu-social" class="menu menu-social">
                    <li id="menu-item-4299" class="footer-social-facebook menu-item menu-item-type-custom menu-item-object-custom"><a href="https://www.facebook.com/NWMAF" target="_blank" rel="noopener noreferrer"><span class="sr-only">Facebook</span></a></li>
                    <li id="menu-item-4298" class="footer-social-x menu-item menu-item-type-custom menu-item-object-custom"><a href="https://twitter.com/NWMAF" target="_blank" rel="noopener noreferrer"><span class="sr-only">X,formerly Twitter</span></a></li>
                    <li id="menu-item-4297" class="footer-social-instagram menu-item menu-item-type-custom menu-item-object-custom"><a href="https://www.instagram.com/nwmafofficial/" target="_blank" rel="noopener noreferrer"><span class="sr-only">Instagram</span></a></li>
                    <li id="menu-item-4300" class="footer-social-youtube menu-item menu-item-type-custom menu-item-object-custom"><a href="https://www.youtube.com/channel/UCYLbNjV3eCQ8jys1YUECimQ?view_as=subscriber" target="_blank" rel="noopener noreferrer"><span class="sr-only">You Tube</span></a></li>
                </ul>
            </div>
            <div class="col-12 footer__site-info">
                <p>The National Women&apos;s Martial Arts Federation is a non-profit organization.</p>
                <address>
                    NWMAF<br>
                    304 S Jones Blvd, #2929<br>
                    Las Vegas, NV 89107<br><br>
                    <a href="mailto:secretary@nwmaf.org">secretary@nwmaf.org</a>
                </address>
                <p>© <?php echo date("Y")?> NWMAF<p>
            </div>
        </div>
    </div>
</footer>
</div><!-- .overflow-container -->

<?php do_action( 'body_bottom' ); ?>

<?php wp_footer(); ?>

</body>
</html>
