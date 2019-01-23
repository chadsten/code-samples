## functions

#' Retreive order data based on date range, and perform calculations on data
#'
#' @param start (int)
#' @param end (int)
#' @param weight (dec)
#'
#' @return result (dataframe)
#' @export
#'
#' @TODO change customerID to parent customerID
#'

get_sales_data <- function(start, end, weight) {
    result <- filter(IR, OrderDate < Sys.Date() - days(start * 30), OrderDate >= Sys.Date() - days(end * 30)) %>%
    group_by(PartNum, Plant) %>%
    summarise(Hits = n() * weight,
              HitsTotal = n(),
              MinShipQty = min(OurShipQty),
              MaxShipQty = max(OurShipQty),
              QtyTotal = sum(OurShipQty),
              Customers = n_distinct(CustID) * weight,
              MonthAvg = sum(OurShipQty) * weight, # we don't divide yet here, just to keep it all in one place
              AvgQty = mean(OurShipQty) * weight,
              LastOrder = max(OrderDate)
              )

    return(result)
}

#' Retreive quote data based on date range, and perform calculations on data
#'
#' @param start (int)
#' @param end (int)
#' @param weight (dec)
#'
#' @return result (dataframe)
#' @export
#'
#' @TODO change customerID to parent customerID
#'
get_quote_data <- function(start, end) {
    result <- filter(QD, DateQuoted < Sys.Date() - days(start * 30), DateQuoted >= Sys.Date() - days(end * 30)) %>%
    group_by(PartNum) %>%
    summarise(Quotes = n(),
              QuoteQtyTotal = sum(QuoteQty),
              QuotedCustomers = n_distinct(CustNum),
              LastQuote = max(as.Date(DateQuoted))
              )

    return(result)
}

#' calculates the min/max value based on suggestion and
#' PUM conversion factor to suggest value divisible by PUM
#'
#' @param conv_factor (int)
#' @param value (int)
#'
#' @return mod (int)
#' @export
#'
pum_mod <- function(conv_factor, value) {
    mod <- 0
    mod <- case_when(
       value == 0 ~ 0, # catch suggested values of 0 to not give them a false minimum
       conv_factor == 1 ~ 0, # don't touch things with a ConvFactor of 1, as we ceiling previously
       value %% conv_factor != 0 ~ conv_factor - (value - ((value %/% conv_factor) * conv_factor)), # handle UOM breaks, to increase up to the next PUM count
       value < conv_factor ~ conv_factor - value, # hanlding of simple case where we raise the value up to just the ConvFactor
       TRUE ~ 0 # handles instances where the sug/factor are already divisible aka "happy accidents"
    )
    
    return(mod)
}

#' determines the item move type based on hits value
#'
#' @param hits (dec)
#'
#' @return imt (str)
#' @export
#'
calculate_imt <- function(hits, contract_count) {
    imt <- "U" # this should never happen
    imt <- case_when(
       hits >= hits_a ~ "A",
       hits >= hits_b ~ "B",
       hits >= hits_c ~ "C",
       hits < hits_c & contract_count < 1 ~ "R",
       hits < hits_c & contract_count > 0 ~ "D", # catch contract R items to not be caught by later processes
       TRUE ~ "E" # this should also never happen
    )

    return(imt)
}

#' determines Item Move Type based on hits
#'
#' @param pum (chr)
#' @param conv_factor (int)
#'
#' @return cf (int)
#' @export
#'
sanitize_cf <- function(pum, cf) {

   cf <- case_when(
      #' @TODO add other UOMClass' default UOMs, or build logic to do it
      pum == "EA" ~ 1,
      pum == "PR" ~ 1,
      pum == "DZ" ~ 12,
      is.na(cf) | cf == 0 ~ lb,
      TRUE ~ cf # catch anything that's not a default when it exists in PartUOM (should be always)
   )

    return(cf)
}

#' calculate change in two numbers, with 0 hanlding incl. cap change to 100%
#'
#' @param new (num)
#' @param old (num)
#' @param cap (bool)
#'
#' @return difference (num)
#' @export
#'
calculate_change <- function(new, old, cap = TRUE) {

    difference <- 0

    # calculate change blindly, we handle 0 below
    difference <- ((new - old) / old) * 100

    # handle one or more fields being 0 by discarding the result to only compare items with data on both ends
    difference <- ifelse(new == 0 | old == 0, 0, difference)

    # set the max change percent to 100 if cap is true
    difference <- ifelse(difference > 100 & cap == TRUE, 100, difference)
    difference <- ifelse(difference < -100 & cap == TRUE, -100, difference)

    return(difference)
}
