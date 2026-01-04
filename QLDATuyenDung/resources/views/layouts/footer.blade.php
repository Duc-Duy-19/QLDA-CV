<footer class="footer footer-modern">
    <div class="container-inner footer-inner">
        <!-- CTA area -->
            <div class="footer-cta">
            <div class="cta-inner">
                    <h2>Hãy để chúng tôi đồng hành cùng bạn</h2>
                </div>
            <!-- decorative strokes removed -->
        </div>

        <div class="footer-grid">
            <div class="footer-col footer-about">
                <div class="logo-container compact">
                    <div class="logo-icon">
                        <div class="logo-shape">
                            <div class="logo-document">
                                <div class="document-header"></div>
                                <div class="document-line"></div>
                                <div class="document-line short"></div>
                                <div class="document-line"></div>
                                <div class="document-line short"></div>
                            </div>
                            <div class="logo-checkmark">
                                <div class="checkmark-line"></div>
                                <div class="checkmark-line"></div>
                            </div>
                        </div>
                        <div class="logo-glow"></div>
                    </div>
                    <div class="logo-text">
                        <span class="web">Web</span>
                        <span class="cv">CV</span>
                    </div>
                </div>
                <p class="muted">Tiếp lợi thế — Nối thành công</p>
                    <p class="small muted">Tuyển dụng, tìm kiếm việc làm trực tuyến.</p>
                    <p class="small muted hotline">Hotline: <a href="tel:0913092424">0913.09.24.24</a></p>
                    <!-- social icons removed as requested -->
            </div>

            <div class="footer-col footer-links">
                <h4>Khám phá</h4>
                <ul>
                    <li><a href="{{ Route::has('home') ? route('home').'#job-categories' : url('/#job-categories') }}">Ngành nghề</a></li>
                    <li><a href="{{ url('/companies') }}">Nhà tuyển dụng</a></li>
                    <li><a href="{{ Route::has('candidate.create-cv') ? route('candidate.create-cv') : url('/create-cv') }}">Tạo CV</a></li>
                </ul>
            </div>

            <div class="footer-col footer-resources">
                <h4>Ngành nghề nổi bật</h4>
                <ul>
                    <li><a href="{{ url('/category/Công%20nghệ%20Thông%20tin') }}">Công nghệ thông tin</a></li>
                    <li><a href="{{ url('/category/Kinh%20doanh') }}">Kinh doanh / Bán hàng</a></li>
                    <li><a href="{{ url('/category/Marketing') }}">Marketing & PR</a></li>
                    <li><a href="{{ url('/category/Kế%20toán') }}">Kế toán</a></li>
                </ul>
            </div>

            <div class="footer-col footer-contact" id="footer-contact">
                <h4>Liên hệ & đăng ký</h4>
                <p class="muted small">Gửi email để nhận cập nhật việc làm và tin tuyển dụng hàng tuần.</p>
                <form id="footer-newsletter" onsubmit="subscribeFooter(event)">
                    <div class="newsletter-row">
                        <input type="email" id="footer-email" placeholder="Nhập email của bạn" required>
                        <button type="submit" class="btn btn-primary">Đăng ký</button>
                    </div>
                </form>
                <div class="contact-block">
                    <div><i class="fas fa-map-marker-alt"></i> 4/6b Văn Chung, P13, Q.Tân Bình, TP.HCM</div>
                    <div><i class="fas fa-phone"></i> <a href="tel:0913092424">0913.09.24.24</a></div>
                    <div><i class="fas fa-envelope"></i> <a href="mailto:brightstar24h@gmail.com">brightstar24h@gmail.com</a></div>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="bottom-left">© {{ date('Y') }} Công ty cổ phần công nghệ BrightStar.</div>
            <div class="bottom-right"></div>
        </div>
    </div>

    <button id="back-to-top" title="Lên đầu trang"><i class="fas fa-arrow-up"></i></button>

    <style>
        /* Enable smooth scrolling for anchor navigation */
        html { scroll-behavior: smooth }
        /* Modern footer styles */
    .footer-modern { background: linear-gradient(180deg,#061018,#031018); color: #d6e7ef; padding: 28px 0 26px; margin-top: 56px; position: relative; overflow: visible }
    /* center and constrain footer content */
    .footer-inner { max-width: 1180px; margin: 0 auto; padding: 0 28px; }
    .footer-cta { position: relative; padding: 46px 0 18px; margin-bottom: 18px; overflow: visible }
    .cta-inner { display:flex; align-items:center; justify-content:space-between }
    .cta-inner h2 { font-size:42px; margin:0; color:#fff; font-weight:700 }
        .footer-decor { position:absolute; right: -40px; top: -10px; pointer-events:none }

    .footer-grid { display: grid; grid-template-columns: 1.3fr 0.9fr 0.9fr 1fr; gap: 36px; align-items: start; column-gap: 48px }
    .footer-col h4 { color: #fff; margin-bottom: 12px; font-size: 16px }
        .footer-col ul { list-style: none; padding:0; margin:0 }
        .footer-col ul li { margin: 8px 0 }
        .footer-col a { color: #bfe8ff; text-decoration: none }
        .footer-col a:hover { color: #fff; text-decoration: underline }
    .logo-container.compact { display:flex; align-items:center; gap:12px }
    .logo-text .web { color:#9ef0b9; font-weight:800; font-size:20px }
    .logo-text .cv { color:#e6fff6; font-weight:800; font-size:20px; margin-left:4px }
    /* center logo and tagline within first column on wide screens; left-align on small screens */
    .footer-about { display:flex; flex-direction:column; align-items:center; text-align:center }
    @media (max-width: 980px) { .footer-about { align-items:flex-start; text-align:left } }
    .footer-about .logo-container { margin-bottom: 10px }
    .footer-about .hotline { margin-top:6px }

    /* social icons: uniform round buttons */
    .socials { margin-top:12px; display:flex; gap:8px }
    .social-link { width:40px; height:40px; display:inline-flex; align-items:center; justify-content:center; border-radius:50%; background: linear-gradient(180deg,#14b463,#059669); color:#fff; box-shadow: 0 8px 18px rgba(4,8,15,0.12); margin-right:8px }
    .social-link i { font-size:16px }
    .muted { color: rgba(230, 245, 255, 0.72); margin-top:8px }
    .small { font-size:13px }

        .newsletter-row { display:flex; gap:8px; margin-top:8px }
        #footer-email { flex:1; padding:12px 14px; border-radius:10px; border:1px solid rgba(255,255,255,0.06); background: rgba(255,255,255,0.02); color: #fff }
        #footer-email::placeholder { color: rgba(255,255,255,0.5) }
        .contact-block { margin-top:12px; color: rgba(230,245,255,0.75); font-size:14px }
        .contact-block div { margin-top:8px }

        /* CTA button style in contact area */
        .btn-primary { background: linear-gradient(180deg,#2dd4bf,#06b6d4); color:#04272b; border:none; padding:10px 14px; border-radius:10px; font-weight:700 }
        .btn-primary:hover { filter:brightness(.95) }

    .footer-bottom { display:flex; justify-content:space-between; align-items:center; margin-top:30px; padding-top:18px; border-top:1px solid rgba(255,255,255,0.04); font-size:13px }
    .footer-bottom .bottom-left, .footer-bottom .bottom-right { padding: 8px 0 }
    .footer-bottom a { color: rgba(255,255,255,0.7); margin-left:16px }

    /* Raised back-to-top so it doesn't overlap floating action buttons on the right */
    #back-to-top { position: fixed; right: 20px; bottom: 320px; width:48px; height:48px; border-radius:50%; background: linear-gradient(180deg,#38b6ff,#1f6feb); color:#fff; border:none; display:flex; align-items:center; justify-content:center; box-shadow: 0 12px 30px rgba(31,111,235,0.18); cursor:pointer; z-index:1100; opacity:0; transform: translateY(6px); transition: opacity .28s, transform .28s }
        #back-to-top.show { opacity:1; transform: translateY(0) }

        /* Responsive */
        @media (max-width: 980px) {
            .footer-inner { padding: 0 20px }
            .footer-cta { padding: 28px 0 8px }
            .cta-inner h2 { font-size:28px }
            .footer-grid { grid-template-columns: 1fr 1fr; gap: 22px }
            .footer-bottom { flex-direction: column; gap:10px; align-items:flex-start }
            #back-to-top { right: 16px; bottom: 180px }
        }
        @media (max-width: 520px) {
            .footer-inner { padding: 0 12px }
            .footer-grid { grid-template-columns: 1fr; gap: 18px }
            #back-to-top { right: 12px; bottom: 140px }
        }
    </style>

    <script>
        // Back-to-top behavior and newsletter mock
        (function(){
            const btn = document.getElementById('back-to-top');
            window.addEventListener('scroll', () => {
                if (window.scrollY > 300) btn.classList.add('show'); else btn.classList.remove('show');
            });
            btn.addEventListener('click', () => { window.scrollTo({ top: 0, behavior: 'smooth' }); });

            window.subscribeFooter = function(e) {
                e.preventDefault();
                const email = document.getElementById('footer-email').value;
                if (!email) return alert('Vui lòng nhập địa chỉ email');
                // Mock subscribe - replace with real API call later
                alert('Cảm ơn! Đã đăng ký: ' + email);
                document.getElementById('footer-email').value = '';
            }
        })();
    </script>
</footer>
