<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ClearOrphanedAttachments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'my:coa';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '고아가 된 첨부 파일을 청소합니다.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $orphaned = \App\Attachment::whereNull('article_id')
            ->where('created_at', '<', \Carbon\Carbon::now()->subWeek())->get();

        $bar = $this->output->createProgressBar(count($orphaned));

        foreach ($orphaned as $attachment) {
            $path = attachments_path($attachment->filename);
            \File::delete($path);
            $attachment->delete();
            $bar->advance();
        }

        $bar->finish();

        return;
    }
}
