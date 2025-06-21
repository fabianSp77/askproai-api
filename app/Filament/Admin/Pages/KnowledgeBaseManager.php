<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class KnowledgeBaseManager extends Page implements HasForms
{
    use InteractsWithForms;
    
    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Verwaltung';
    protected static ?string $navigationLabel = 'Wissensdatenbank';
    protected static string $view = 'filament.admin.pages.knowledge-base-manager';
    protected static ?int $navigationSort = 20;
    protected static ?string $slug = 'knowledge-base';
    
    public $category = '';
    public $topic = '';
    public $articleTitle = '';
    public $content = '';
    public $selectedArticle = null;
    
    public function mount()
    {
        // Initialize with empty form
    }
    
    public function getFormSchema(): array
    {
        return [
            Select::make('category')
                ->label('Kategorie')
                ->options($this->getCategoryOptions())
                ->required()
                ->reactive()
                ->afterStateUpdated(fn () => $this->reset('topic', 'articleTitle', 'content')),
                
            Select::make('topic')
                ->label('Artikel')
                ->options(fn () => $this->getTopicOptions())
                ->visible(fn () => !empty($this->category))
                ->reactive()
                ->afterStateUpdated(fn () => $this->loadArticle()),
                
            TextInput::make('articleTitle')
                ->label('Titel')
                ->required()
                ->visible(fn () => !empty($this->category)),
                
            MarkdownEditor::make('content')
                ->label('Inhalt')
                ->required()
                ->visible(fn () => !empty($this->category))
                ->toolbarButtons([
                    'heading',
                    'bullet',
                    'orderedList',
                    'bold',
                    'italic',
                    'link',
                    'code',
                    'blockquote',
                ]),
        ];
    }
    
    public function getCategoryOptions(): array
    {
        return [
            'getting-started' => 'Erste Schritte',
            'appointments' => 'Termine verwalten',
            'account' => 'Ihr Konto',
            'billing' => 'Rechnungen & Zahlungen',
            'troubleshooting' => 'Fehlerbehebung',
            'faq' => 'Häufige Fragen',
        ];
    }
    
    public function getTopicOptions(): array
    {
        if (empty($this->category)) {
            return [];
        }
        
        $options = ['new' => '+ Neuer Artikel'];
        
        $categoryPath = resource_path("docs/help-center/{$this->category}");
        if (File::exists($categoryPath)) {
            $files = File::files($categoryPath);
            foreach ($files as $file) {
                if ($file->getExtension() === 'md') {
                    $topic = str_replace('.md', '', $file->getFilename());
                    $content = File::get($file);
                    $title = $this->extractTitle($content);
                    $options[$topic] = $title;
                }
            }
        }
        
        return $options;
    }
    
    public function loadArticle()
    {
        if (empty($this->topic) || $this->topic === 'new') {
            $this->reset('articleTitle', 'content');
            return;
        }
        
        $filePath = resource_path("docs/help-center/{$this->category}/{$this->topic}.md");
        if (File::exists($filePath)) {
            $markdown = File::get($filePath);
            $this->articleTitle = $this->extractTitle($markdown);
            // Remove the title from content since we have a separate field
            $this->content = preg_replace('/^#\s+.+\n\n?/m', '', $markdown);
        }
    }
    
    public function save()
    {
        $this->validate([
            'category' => 'required',
            'articleTitle' => 'required',
            'content' => 'required',
        ]);
        
        // Generate topic slug from title if new article
        if ($this->topic === 'new' || empty($this->topic)) {
            $this->topic = Str::slug($this->articleTitle);
        }
        
        // Ensure directory exists
        $categoryPath = resource_path("docs/help-center/{$this->category}");
        if (!File::exists($categoryPath)) {
            File::makeDirectory($categoryPath, 0755, true);
        }
        
        // Prepare content with title
        $fullContent = "# {$this->articleTitle}\n\n{$this->content}";
        
        // Save file
        $filePath = "{$categoryPath}/{$this->topic}.md";
        File::put($filePath, $fullContent);
        
        Notification::make()
            ->title('Artikel gespeichert')
            ->success()
            ->send();
            
        // Reload topic options to include the new article
        $this->reset('topic');
    }
    
    public function delete()
    {
        if (empty($this->topic) || $this->topic === 'new') {
            return;
        }
        
        $filePath = resource_path("docs/help-center/{$this->category}/{$this->topic}.md");
        if (File::exists($filePath)) {
            File::delete($filePath);
            
            Notification::make()
                ->title('Artikel gelöscht')
                ->success()
                ->send();
                
            $this->reset('topic', 'articleTitle', 'content');
        }
    }
    
    protected function extractTitle($markdown)
    {
        if (preg_match('/^#\s+(.+)$/m', $markdown, $matches)) {
            return $matches[1];
        }
        return 'Untitled';
    }
    
    public function getStats(): array
    {
        $totalArticles = 0;
        $categoryCounts = [];
        
        foreach ($this->getCategoryOptions() as $key => $name) {
            $categoryPath = resource_path("docs/help-center/{$key}");
            $count = 0;
            
            if (File::exists($categoryPath)) {
                $files = File::files($categoryPath);
                foreach ($files as $file) {
                    if ($file->getExtension() === 'md') {
                        $count++;
                        $totalArticles++;
                    }
                }
            }
            
            $categoryCounts[$name] = $count;
        }
        
        return [
            'total' => $totalArticles,
            'byCategory' => $categoryCounts,
        ];
    }
}