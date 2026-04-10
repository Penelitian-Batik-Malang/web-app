<footer class="bg-dark border-t border-secondary mt-auto">
    <div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            {{-- BatikMalang.ai Section --}}
            <div class="space-y-4">
                <h3 class="text-lg font-bold text-white">
                    BatikMalang<span class="text-primary">.ai</span>
                </h3>
                <p class="text-gray-400 text-sm leading-relaxed">
                    Dikembangkan melalui kemitraan khusus dengan tiga sentra batik terkemuka di Malang.
                </p>
                <ul class="space-y-2 text-gray-400 text-sm">
                    <li class="flex items-start">
                        <span class="mr-2">•</span>
                        <span>Batik Blimbing</span>
                    </li>
                    <li class="flex items-start">
                        <span class="mr-2">•</span>
                        <span>Batik Soeandari</span>
                    </li>
                    <li class="flex items-start">
                        <span class="mr-2">•</span>
                        <span>Rumah Seni Budaya Singhasari</span>
                    </li>
                </ul>
            </div>

            {{-- Dibuat oleh Section --}}
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-white">Dibuat oleh</h3>
                <ul class="space-y-2 text-gray-400 text-sm">
                    <li>Mamluatul Hani'ah</li>
                    <li>Vivi Nur Wijayaningrum</li>
                    <li>Wilda Imama Salsabilla</li>
                </ul>
            </div>

            {{-- Kontak Section --}}
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-white">Kontak</h3>
                <ul class="space-y-2 text-gray-400 text-sm">
                    <li class="flex items-center gap-2">
                        <a href="https://www.instagram.com/jtipolinema" target="_blank" class="flex items-center gap-2 hover:text-pink-600 transition-colors">
                            <i class="bi bi-instagram"></i>
                            <span>jtipolinema</span>
                        </a>
                    </li>
                    <li class="flex items-center gap-2">
                        <a href="https://www.instagram.com/polinema_campus" target="_blank" class="flex items-center gap-2 hover:text-pink-600 transition-colors">
                            <i class="bi bi-instagram"></i>
                            <span>polinema_campus</span>
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Alamat Section --}}
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-white">Alamat</h3>
                <address class="not-italic text-gray-400 text-sm leading-relaxed">
                    <p>Politeknik Negeri Malang</p>
                    <p>Jalan Soekarno Hatta No. 9</p>
                    <p>Kota Malang, Jawa Timur</p>
                </address>
            </div>
        </div>

        {{-- Google Maps Section --}}
        <div class="mt-8 pt-8 border-t border-gray-800">
            <h3 class="text-sm font-semibold text-white mb-3 flex items-center gap-2">
                <i class="bi bi-geo-alt-fill text-primary"></i>
                Lokasi Kampus — Politeknik Negeri Malang
            </h3>
            <div class="rounded-xl overflow-hidden border border-gray-700 shadow-lg" style="height: 200px;">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3951.5048498774654!2d112.6136!3d-7.9476!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2dd629b22c78d661%3A0x6d0408e0c3e555d0!2sPoliteknik%20Negeri%20Malang!5e0!3m2!1sid!2sid!4v1699900000000!5m2!1sid!2sid"
                    width="100%" 
                    height="100%" 
                    style="border:0;" 
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade"
                    title="Lokasi Politeknik Negeri Malang - Jl. Soekarno Hatta No.9, Malang"
                ></iframe>
            </div>
            <p class="text-gray-600 text-xs mt-2">Jl. Soekarno Hatta No.9, Jatimulyo, Kec. Lowokwaru, Kota Malang, Jawa Timur 65141</p>
        </div>

        {{-- Footer Bottom --}}
        <div class="mt-8 pt-8 border-t border-gray-800">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4 text-gray-500 text-sm">
                <p>Copyright {{ date('Y') }} BatikMalang.ai</p>
                <p>No Surat Kementrian</p>
            </div>
        </div>
    </div>
</footer>
