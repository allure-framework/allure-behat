export default {
  name: "Allure Behat",
  output: "./out/allure-report",
  plugins: {
    testops: {
      options: {
        launchName: `Allure Behat GitHub actions run (${new Date().toISOString()})`,
      },
    },
  },
};
