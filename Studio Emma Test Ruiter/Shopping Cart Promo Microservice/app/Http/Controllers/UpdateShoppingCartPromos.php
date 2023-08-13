<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Console\Command;
//use Symfony\Component\Console\Output\ConsoleOutput;

class UpdateShoppingCartPromos extends Controller
{
    public function PromoCalculator(Request $request){
        //Variable to keep track of total discount
        $TotalDiscount = 0;
        //variable for percentage discount
        $percentDiscount = 20;
        //variable to calculate new total price
        $newTotalPrice;
        //Decode json request data
        $requestBodyJson = json_decode(file_get_contents('php://input'));
        //Shopping cart array
        $ShoppingCart = $requestBodyJson[0]->ShoppingCart;

        //Check if any buy 1 take 1 is set then set variable for product IDs which has buy 1 get 2nd free promo
        if (isset($requestBodyJson[1]->Buy1Take1ProductIds))$Buy1Get1ProductIds = $requestBodyJson[1]->Buy1Take1ProductIds;

        //Check if any percentage discount is set then set variable for Product IDs of products with a percentage off
        if (isset($requestBodyJson[1]->PercentOffProductIds))$percentDiscountProductIDs = $requestBodyJson[1]->PercentOffProductIds;

        //Calculate Discounts if any
        if (isset($Buy1Get1ProductIds)){
            $Buy1Get1Discounted = $this->GetTotalBuy1Take1Discount($ShoppingCart, $Buy1Get1ProductIds);
            $TotalDiscount = $TotalDiscount + $Buy1Get1Discounted;
        }
        if (isset($percentDiscountProductIDs)){
            $TotalPercentOffDiscount = $this->GetTotalPercentOffDiscount($ShoppingCart, $percentDiscountProductIDs, $percentDiscount);
            $TotalDiscount = $TotalDiscount + $TotalPercentOffDiscount;
        }
        
        //subtract discount from total price
        $newTotalPrice = $requestBodyJson[1]->TotalPrice+$TotalDiscount;

        //add new "TotalPrice" and "TotalDiscount" to the shopping cart data
        $requestBodyJson[1]->TotalPrice = $newTotalPrice;
        $requestBodyJson[1]->TotalDiscount = $TotalDiscount;
        //return the updated Shopping Cart data
        return $requestBodyJson;

        
    }
    //Subtract discount from total Price
    public function DiscountFromTotalAmount($totalPrice, $discountAmount){
        return ($totalPrice - $discountAmount);
    }

    public function GetTotalBuy1Take1Discount($cart, $IDsfromProductWithDiscount){
        //used to track item count
        $itemCount=0;
        //used to set result array
        $result = 0;
        //iterate trough all items in cart
        foreach($cart as $item){
            
            //check if product ID is in given product IDs for buy1take1 AND quantity of item is more than 2
            if (in_Array($item->ProductID, $IDsfromProductWithDiscount) & $item->Quantity >=2){
                //if quantity is an uneven number
                if($item->Quantity % 2 == 1){
                    //return item quantity minus one --> divided by two ---> times the unit Price
                    $result = $result + ((($item->Quantity-1)/2)*($item->UnitPriceVatIncluded));
                }
                //if quantity is an even number
                else {
                    //return item quantity divided by two ---> times the unit Price
                    $result = $result + ((($item->Quantity)/2)*($item->UnitPriceVatIncluded));
                }

                $itemCount +=1;
            }

        }
        return $result*(-1);
    }
    public function GetTotalPercentOffDiscount($cart, $percentDiscountProductIDs, $percentOff){
        //used to track item count
        $itemCount=0;
        //used to set result array
        $result = 0;
        //check per item if the ID is present in the PercentDiscount Array
        foreach($cart as $item){
            //If item has percentage discount
            if (in_Array($item->ProductID, $percentDiscountProductIDs)){
                //calculate discount and add to result
                $result = $result + (($item->UnitPriceVatIncluded)*($percentOff/100));
            }
            $itemCount +=1;
        }
        //return total percentage discount
        return $result*(-1);
    }
}
