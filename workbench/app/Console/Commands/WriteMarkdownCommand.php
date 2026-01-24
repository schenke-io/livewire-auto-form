<?php

namespace Workbench\App\Console\Commands;

use Illuminate\Console\Command;
use SchenkeIo\PackagingTools\Markdown\MarkdownAssembler;

class WriteMarkdownCommand extends Command
{
    protected $signature = 'workbench:write-markdown';

    protected $description = 'Generate the README.md file';

    public function handle(): void
    {
        $this->info('Generating README.md...');

        $assembler = new MarkdownAssembler('workbench/resources/md');
        $assembler->autoHeader('Livewire Auto Form');

        $assembler->addMarkdown('why.md');
        $assembler->addMarkdown('introduction.md');
        $assembler->addTableOfContents();
        $assembler->addMarkdown('example.md');
        $assembler->addMarkdown('wizard.md');
        $assembler->addMarkdown('definitions.md');

        $assembler->writeMarkdown('README.md');

        $this->info('README.md generated successfully.');
    }
}
