<?php

namespace App\Console\Commands;

use App\Models\ProductDiscount;
use App\Services\ProductServices\DiscountService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class CheckExpiredDiscounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'discounts:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tarihi geçmiş indirimleri kontrol eder ve ürün fiyat tarihçesini günceller.';

    /**
     *
     */
    public function __construct(public DiscountService $discountService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @throws Throwable
     */
    public function handle(): int
    {
        $now = Carbon::now();

        // 1) Süresi dolmuş ve hâlâ aktif indirimleri al
        $expiredDiscounts = ProductDiscount::query()
                                           ->where('is_active', true)
                                           ->where('discount_end', '<', $now)
                                           ->get();

        if ($expiredDiscounts->isEmpty()) {
            $this->info('Aktif ve süresi dolmuş indirim bulunamadı.');
            return 0;
        }

        DB::transaction(function() use ($expiredDiscounts) {
            foreach ($expiredDiscounts as $discount) {
                // setDiscount ile modeli servise yüklüyoruz,
                // changeStatus() içinde hem revert hem de apply akışları çalışıyor
                $this->discountService
                    ->setDiscount($discount)
                    ->changeStatus();
            }
        });

        $this->info($expiredDiscounts->count()
                    . ' adet indirim işlendi; status değişimi, history ve gerekiyorsa yeni indirim uygulaması tek noktadan yapıldı.');
        return 0;
    }
}
