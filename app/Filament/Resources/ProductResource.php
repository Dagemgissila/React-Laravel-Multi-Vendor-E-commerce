<?php

namespace App\Filament\Resources;

use App\Enums\EnumsProductsStatusEnum;
use App\Enums\RoleEnum;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\Pages\ProductImages;
use App\Filament\Resources\ProductResource\Pages\ProductVariationTypes;
use App\Models\Product;
use App\Models\ProductVariation;
use Faker\Provider\Image;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Str;
use Filament\Resources\Pages\Page;


class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-s-queue-list';
    protected static SubNavigationPosition $subNavigationPosition = SubNavigationPosition::End;


    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->forVendor();
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make()
                    ->schema([

                        TextInput::make("title")
                            ->live(onBlur: true)
                            ->required()
                            ->afterStateUpdated(function (string $operation, $state, callable $set) {
                                $set('slug', Str::slug($state));
                            }),
                        TextInput::make("slug")
                            ->required(),
                        Select::make("department_id")
                            ->relationship('department', 'name')
                            ->label("Department")
                            ->preload()
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (callable $set) {
                                $set('category_id', null);
                            }),
                        Select::make('category_id')
                            ->relationship(
                                "category",
                                'name',
                                modifyQueryUsing: function (Builder $query, callable $get) {
                                    $departmentId = $get('department_id');
                                    if ($departmentId) {
                                        $query->where("department_id", $departmentId);
                                    }
                                }
                            )
                            ->label("Category")
                            ->preload()
                            ->searchable()
                    ]),
                RichEditor::make("description")
                    ->required()
                    ->toolbarButtons([
                        'blockquote',
                        'bold',
                        'bulletList',
                        'h2',
                        'h3',
                        'italic',
                        "link",
                        'orderedList',
                        'redo',
                        'strike',
                        'underline',
                        'undo',
                        'table'
                    ])
                    ->columnSpan(2),
                TextInput::make("price")
                    ->required()
                    ->numeric(),
                TextInput::make('quantity')
                    ->integer(),
                Select::make("status")
                    ->options(EnumsProductsStatusEnum::labels())
                    ->default(EnumsProductsStatusEnum::Draft->value)
                    ->required()




            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('images')
                    ->collection('images')
                    ->limit(1)
                    ->conversion('thumb'),
                TextColumn::make("title")
                    ->searchable()
                    ->sortable()
                    ->words(10),
                TextColumn::make("status")
                    ->badge()
                    ->colors(EnumsProductsStatusEnum::colors()),

                TextColumn::make("department.name"),
                TextColumn::make("category.name"),
                TextColumn::make("created_at")
                    ->dateTime(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(EnumsProductsStatusEnum::labels()),
                SelectFilter::make('department')
                    ->relationship('department', 'name')
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
            'images' => Pages\ProductImages::route('/{record}/images'),
            'variation-types' => Pages\ProductVariationTypes::route('/{record}/variation-types'),
            'variations' => Pages\ProductVariations::route('/{record}/variations'),
        ];
    }


    public static function getRecordSubNavigation(Page $page): array
    {
        return
            $page->generateNavigationItems([
                EditProduct::class,
                ProductImages::class,
                ProductVariationTypes::class,
                Pages\ProductVariations::class,
            ]);
    }



    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();
        return $user && $user->hasRole(RoleEnum::Vendor);
    }

}
