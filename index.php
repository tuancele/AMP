<?php
$post_counter = 0; // Thêm dòng này
/**
 * index.php - Đã kích hoạt lại ảnh đại diện
 * SỬA LỖI: Thêm aria-label vào link ảnh để tăng khả năng tiếp cận.
 */
get_header();
?>
<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
    <article id="post-<?php the_ID(); ?>" <?php post_class('entry ap--content'); ?>>

        <header class="entry-header">
            <?php if (has_post_thumbnail()) : ?>
                <div class="entry-image-thumb">
                    <a href="<?php the_permalink(); ?>" aria-label="<?php the_title_attribute(); ?>">
                        <?php 
                        // the_post_thumbnail sẽ được filter sang amp-img trong functions.php
                        the_post_thumbnail('medium_large', array(
                            'width'  => 600, 
                            'height' => 300, 
                        )); 
                        ?>
                    </a>
                </div>
            <?php endif; ?>

            <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        </header>

        <div><?php the_excerpt(); ?></div>
    </article>
<?php endwhile; else : ?>
    <p>No content found.</p>
<?php endif; ?>
<?php get_footer(); ?>