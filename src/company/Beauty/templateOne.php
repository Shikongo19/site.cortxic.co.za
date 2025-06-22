<div>
    <div id="carouselExampleSlidesOnly" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
            <div class="carousel-item active">
            <img src="http://localhost/site.cortxic.co.za/assets/companies/beauty/background/bg1.jpg" class="d-block w-100" alt="...">
            </div>
            <div class="carousel-item">
            <img src="http://localhost/site.cortxic.co.za/assets/companies/beauty/background/bg2.jpg" class="d-block w-100" alt="...">
            </div>
            <div class="carousel-item">
            <img src="http://localhost/site.cortxic.co.za/assets/companies/beauty/background/bg3.jpg" class="d-block w-100" alt="...">
            </div>
        </div>
    </div>
    <div class="fullflex" style="position: relative; width: 100%; height: 15vh;">
        <div class="container rounded border-p shadow bg-light" style="position: absolute; top: -3rem; z-index: 100;">
            <h3>Make Appointment</h3>
            <form action="" method="post" class="flex-wrap align-items-center">
                <div class="flex-raw p-2">
                    <label  for="service-name">Service</label>
                    <select class="w-100" name="service" id="service">
                        <option value="">Twist</option>
                        <option value="">Twist</option>
                        <option value="">Twist</option>
                    </select>
                </div>
                <div class="flex-raw p-2">
                    <label  for="service-name">Date</label>
                    <input class="w-100" type="datetime-local" name="date" id="date">
                </div>
                <div class="pt-3">
                    <button type="submit" class="btn btn-primary">Book</button>
                </div>
            </form>
        </div>
    </div>
    <div class="container p-2 d-flex justify-content-between">
        <h2>Today's Bookings</h2>
        <div class="p-2">
            <div><?php echo date('l, F j, Y'); ?></div>
            <div id="digital-clock" style="font-family: monospace; font-size: 1.2em;"></div>
        </div>
    </div>
    <div class="container flex-wrap align-items-center justify-content-center gap-2 mt-4 p-4 bg-light rounded">
        <div class="card p-2">
            <h3>Twist</h3>
            <p>TIME</p>
            <h3>10h300 - 11h00</h3>
        </div>
    </div>
    <div class="container mt-4">
        <h2 class="pt-4" style="font-weight: bold;">Our Services</h2>
    </div>
    <script>
        function updateDigitalClock() {
            const now = new Date();
            let hours = now.getHours();
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const seconds = now.getSeconds().toString().padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            
            // Convert to 12-hour format
            hours = hours % 12;
            hours = hours ? hours : 12; // the hour '0' should be '12'
            
            const timeString = `${hours}:${minutes}:${seconds} ${ampm}`;
            document.getElementById('digital-clock').textContent = timeString;
            
            setTimeout(updateDigitalClock, 1000);
        }
        
        updateDigitalClock();
    </script>
</div>