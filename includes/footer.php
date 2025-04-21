<?php
// includes/footer.php
?>
    </main>

    <div class="flex-grow"></div><!-- Spacer to push footer down -->

    <footer class="bg-gray-800 text-white py-6 mt-auto">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between">
                <div class="mb-6 md:mb-0">
                    <h2 class="text-2xl font-bold text-orange-500">Krauk-Krauk</h2>
                    <p class="mt-2">Pesan makanan favoritmu dengan mudah</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-2">Kontak</h3>
                    <p><i class="fas fa-envelope mr-2"></i> info@Krauk-Krauk.com</p>
                    <p><i class="fas fa-phone mr-2"></i> +62 123 4567 890</p>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-6 pt-4 text-center">
                <p>&copy; <?= date('Y') ?> Krauk-Krauk. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animasi untuk hero section
            const heroSection = document.querySelector('.hero-section');
            if (heroSection) {
                window.addEventListener('scroll', function() {
                    const scrollPosition = window.scrollY;
                    if (scrollPosition > 100) {
                        heroSection.classList.add('opacity-0');
                        setTimeout(() => {
                            heroSection.style.display = 'none';
                        }, 500);
                    }
                });
            }
        });
    </script>
</body>
</html>