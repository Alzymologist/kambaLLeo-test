//! EUSART interface for DEBUG

use alloc::string::String;
use efm32pg23_fix::{GPIO_S, Peripherals};
use crate::peripherals::gpio_pins::*;

const BAUDRATE_DEBUG_EUSART: u32 = 115_200;

// pub fn pr(s:String) {
 //   unsafe {
 //       write_debug_eusart_string(s);
 //   }
// }

/// setting up EUSART1, for DEBUG
///
/// why gpio setup is before init? does the order matter at all?
pub fn init_debug_eusart(peripherals: &mut Peripherals) {

    // peripherals.CMU_S.clken1.write(|w_reg| w_reg.eusart1().set_bit());
    // peripherals.CMU_S.clken1.set_bit(1 << 1);
    // write(|w_reg| w_reg.eusart1().set_bit());

    // DEBUG TX routing
    peripherals
        .GPIO_S
        .eusart1_txroute
        .write(|w_reg| {
            w_reg
                .port().variant(1) // lleo было 2
                .pin().variant(DEBUG_TX_PIN)
    });

    // // DEBUG RX routing
    // peripherals
    //     .GPIO_S
    //     .eusart1_rxroute
    //     .write(|w_reg| {
    //         w_reg
    //             .port().variant(1) // lleo было 2
    //             .pin().variant(DEBUG_RX_PIN)
    // });

    // route enable
    peripherals
        .GPIO_S
        .eusart1_routeen
        .write(|w_reg| {
            w_reg
                .txpen().set_bit()
            //    .rxpen().set_bit()
    });

    // reset EUSART
    debug_eusart_reset(peripherals);

    // calculate clkdiv
    //    let clkdiv: u8 = (19_000_000/BAUDRATE_DEBUG_EUSART - 1).try_into().expect("BAURATE_EUSART is expected to not exceed and be comparable to reference frequency");
    //    let clkdiv = ((19_000_000 - 1)/(2*BAUDRATE_DEBUG_EUSART)) << 8;
    let clkdiv: u32 = 300; // EFM Width: 8.677 Freq: 11.52

    peripherals
    .EUSART1_S
    .clkdiv
    .write(|w_reg|
        w_reg
            .div().variant(clkdiv.into())
    );

    debug_eusart_enable(peripherals);

    while peripherals.EUSART1_S.status.read().rxidle().bit_is_clear()
        | peripherals.EUSART1_S.status.read().txidle().bit_is_clear()  {}

    // remember to reset connected ram device here later, right after setup

// ====================================================

}

fn debug_eusart_disable(peripherals: &mut Peripherals) {
    if peripherals
        .EUSART1_S
        .en
        .read()
        .en()
        .bit_is_set()
    {
        if peripherals.EUSART1_S.cfg0.read().sync().bit_is_clear() | peripherals.EUSART1_S.cfg2.read().master().bit_is_set() {
            // disable TX and RX
            peripherals.EUSART1_S.cmd.write(|w_reg| w_reg.rxdis().set_bit().txdis().set_bit());

            // wait for TXDIS and RXDIS to pass
            while peripherals.EUSART1_S.syncbusy.read().rxdis().bit_is_set() | peripherals.EUSART1_S.syncbusy.read().txdis().bit_is_set() {}

            // wait for TX and RX enable status to go low
            while peripherals.EUSART1_S.status.read().rxens().bit_is_set() | peripherals.EUSART1_S.status.read().txens().bit_is_set() {}
        }

        peripherals
            .EUSART1_S
            .en
            .write(|w_reg| w_reg.en().clear_bit());

        // wait for disabling to clear
        while peripherals.EUSART1_S.en.read().disabling().bit_is_set() {}
    }
}

fn debug_eusart_enable(peripherals: &mut Peripherals) {
    peripherals
        .EUSART1_S
        .en
        .write(|w_reg| w_reg.en().set_bit());

    while peripherals.EUSART1_S.syncbusy.read().bits().ne(&0) {}

    peripherals
        .EUSART1_S
        .cmd
        .write(|w_reg| {
            w_reg
                .rxen().set_bit()
                .rxdis().clear_bit()
                .txen().set_bit()
                .txdis().clear_bit()
    });

    while peripherals.EUSART1_S.syncbusy.read().rxen().bit_is_set()
        | peripherals.EUSART1_S.syncbusy.read().rxdis().bit_is_set()
        | peripherals.EUSART1_S.syncbusy.read().txen().bit_is_set()
        | peripherals.EUSART1_S.syncbusy.read().txdis().bit_is_set()
    {}

    while peripherals.EUSART1_S.status.read().rxens().bit_is_clear()
        | peripherals.EUSART1_S.status.read().txens().bit_is_clear()
    {}
}

fn debug_eusart_reset(peripherals: &mut Peripherals) {
    debug_eusart_disable(peripherals);

    for _i in 0..4 {
        peripherals.EUSART1_S.cfg2.write(|w_reg| w_reg.clkpha().set_bit());
        peripherals.EUSART1_S.cfg2.write(|w_reg| w_reg.clkpha().clear_bit());
    }

    peripherals.EUSART1_S.cfg2.reset();
    peripherals.EUSART1_S.cfg1.reset();
    peripherals.EUSART1_S.cfg0.reset();
    peripherals.EUSART1_S.framecfg.reset();
    peripherals.EUSART1_S.dtxdatcfg.reset();
    peripherals.EUSART1_S.timingcfg.reset();
    peripherals.EUSART1_S.irhfcfg.reset();
    peripherals.EUSART1_S.startframecfg.reset();
    peripherals.EUSART1_S.sigframecfg.reset();
    peripherals.EUSART1_S.trigctrl.reset();
    peripherals.EUSART1_S.ien.reset();
    peripherals.EUSART1_S.if_.reset();
    peripherals.EUSART1_S.clkdiv.reset();
}


