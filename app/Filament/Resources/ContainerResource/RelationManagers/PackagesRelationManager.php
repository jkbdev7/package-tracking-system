<?php

namespace App\Filament\Resources\ContainerResource\RelationManagers;

use App\Models\Package;
use App\Models\PackageType;
use App\Models\ShippingType;
use App\Models\ShippingTypeState;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PackagesRelationManager extends RelationManager
{
    protected static string $relationship = 'packages';

    public function isReadOnly(): bool
    {
        return false; // TODO: Change the autogenerated stub
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('tracker_number')
                    ->default('OR-' . random_int(100000, 999999))
                    ->disabled()
                    ->dehydrated()
                    ->required()
                    ->maxLength(32)
                    ->unique(Package::class, 'tracker_number', ignoreRecord: true),
                Forms\Components\Select::make('package_type_id')
                    ->live()
                    ->relationship('packageType','name')
                    ->required(),
                Forms\Components\Select::make('shipping_type_id')
                    ->live()
                    ->relationship('shippingType','name')
                    ->required(),
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('Phone')
                            ->required()
                            ->maxLength(255)
                            ->unique(),

                        Forms\Components\TextInput::make('city')
                            ->maxLength(255)
                    ])
                    ->createOptionAction(function (Action $action) {
                        return $action
                            ->modalHeading('Create customer')
                            ->modalButton('Create customer')
                            ->modalWidth('lg');
                    }),
                Forms\Components\Select::make('shipping_type_state_id')
                    ->relationship('shippingTypeState', 'status_name')
                    ->searchable()
                    ->preload()
                    ->options(function (Get $get) {
                        $shippingTypeId = $get('shipping_type_id');

                        if (is_null($shippingTypeId)) {
                            return collect();
                        }
                        return ShippingTypeState::query()
                            ->where('shipping_type_id', $shippingTypeId)
                            ->pluck('status_name', 'id');
                    })
                    ->required(),
                Forms\Components\TextInput::make('size')
                    ->live()
                    ->required()
                    ->numeric(),
                Placeholder::make('price')
                    ->content(function (Get $get ,Set $set){
                        $defaultPrice = 1;
                        $packagePrice = $defaultPrice;
                        $shippingPrice = $defaultPrice;
                        $size = $get('size') ?: $defaultPrice;

                        if ($packageTypeId = $get('package_type_id')) {
                            $package = PackageType::find($packageTypeId);
                            $packagePrice = $package ? $package->price : $defaultPrice;
                        }
                        if ($shippingTypeId = $get('shipping_type_id')) {
                            $shipping = ShippingType::find($shippingTypeId);
                            $shippingPrice = $shipping ? $shipping->price : $defaultPrice;
                        }
                        $total = $packagePrice * $shippingPrice * $size;
                        return $total;
                    }),
                Forms\Components\TextInput::make('ctn')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('weight')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('notes')
                    ->maxLength(65535),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('tracker_number')
            ->columns([
                Tables\Columns\TextColumn::make('tracker_number')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_collected')
                    ->boolean()
                    ->searchable(),
                Tables\Columns\TextColumn::make('packageType.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('shippingType.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('shippingTypeState.status_name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('size')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ctn')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('weight')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('notes')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
